<?php

declare(strict_types=1);

namespace App\Service\NoteProcessor;

use App\Constant\Note\Approval;
use App\Constant\Review\Status;
use App\Entity\Comment;
use App\Entity\Review;
use App\Service\ReviewService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

class ApprovalNoteProcessor extends AbstractNoteProcessor implements NoteProcessorInterface
{
    private ReviewService $reviewService;

    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;

    public function __construct(
        ReviewService $reviewService,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->reviewService = $reviewService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function supports(Comment $comment): bool
    {
        return $this->isApprovalComment($comment);
    }

    public function process(Comment $comment): void
    {
        $review = $this->reviewService->createIfNotExists($comment);
        $connection = $this->entityManager->getConnection();

        try {
            $connection->beginTransaction();
            $this->entityManager->lock($review, LockMode::PESSIMISTIC_WRITE);

            $this->processReview($review, $comment);
            $connection->commit();

        } catch (Exception $exception) {
            $this->logger->error('Error, rollbacking transaction', ['exception' => $exception]);
            $connection->rollBack();
        }
    }

    protected function processReview(Review $review, Comment $comment): void
    {
        if ($review->getStatus() === Status::COMPLETED) {
            $this->logger->warning('Merge request already completed', ['review' => $review->getId()]);
            return;
        }

        if ($this->doesCommentBelongToMergeRequestAuthor($comment, $review)) {
            $this->logger->warning('Comment belongs to MR author', ['review' => $review->getId()]);
            return;
        }

        if ($this->hasAlreadyApproved($comment, $review)) {
            $this->logger->warning('MR already approved', ['review' => $review->getId()]);
            return;
        }

        $review->addReviewer($comment->getAuthor());
        $review->increaseApprovalCount();

        $this->reviewService->notifyAboutCompletion($review);
    }

    protected function doesCommentBelongToMergeRequestAuthor(Comment $comment, Review $review): bool
    {
        $mergeRequestAuthor = $review->getMergeRequest()->getAuthor();
        $commentAuthor = $comment->getAuthor();

        return $mergeRequestAuthor->getId() === $commentAuthor->getId();
    }

    private function hasAlreadyApproved(Comment $comment, Review $review): bool
    {
        $reviewComments = $review->getComments();
        foreach ($reviewComments as $reviewComment) {
            if ($reviewComment->getId() === $comment->getId()) {
                continue;
            }

            if ($comment->getAuthor()->getId() !== $reviewComment->getAuthor()->getId()) {
                continue;
            }

            if ($this->isApprovalComment($reviewComment)) {
                return true;
            }
        }

        return false;
    }

    private function isApprovalComment(Comment $comment): bool
    {
        foreach (Approval::toArray() as $note) {
            if (stripos($comment->getNote(), $note) !== false) {
                return true;
            }
        }

        return false;
    }
}
