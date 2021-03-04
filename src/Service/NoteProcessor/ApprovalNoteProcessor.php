<?php

declare(strict_types=1);

namespace App\Service\NoteProcessor;

use App\Constant\Note\Approval;
use App\Constant\Review\Status;
use App\Entity\Comment;
use App\Entity\Review;
use App\Service\ReviewService;

class ApprovalNoteProcessor implements NoteProcessorInterface
{
    private $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function supports(Comment $comment): bool
    {
        return $this->isApprovalComment($comment);
    }

    public function process(Comment $comment): void
    {
        $review = $this->reviewService->createIfNotExists($comment);
        if ($review->getStatus() === Status::COMPLETED) {
            return;
        }

        if ($this->doesCommentBelongToMergeRequestAuthor($comment, $review)) {
            return;
        }

        if ($this->hasAlreadyApproved($comment, $review)) {
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
