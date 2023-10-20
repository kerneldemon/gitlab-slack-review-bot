<?php

declare(strict_types=1);

namespace App\Service;

use App\Constant\Gitlab\SystemUser;
use App\Constant\Review\Status;
use App\Entity\Comment;
use App\Entity\Review;
use App\Mappers\ScopeToNumberOfReviewersMapper;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

class ReviewService
{
    private $entityManager;

    private $chatService;

    private $reviewRepository;

    private $authorService;

    private $scopeToNumberOfReviewersMapper;

    private $gitlabService;

    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ChatService $chatService,
        ReviewRepository $reviewRepository,
        AuthorService $authorService,
        ScopeToNumberOfReviewersMapper $scopeToNumberOfReviewersMapper,
        GitlabService $gitlabService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->chatService = $chatService;
        $this->reviewRepository = $reviewRepository;
        $this->authorService = $authorService;
        $this->scopeToNumberOfReviewersMapper = $scopeToNumberOfReviewersMapper;
        $this->gitlabService = $gitlabService;
        $this->logger = $logger;
    }

    public function findByComment(Comment $comment): ?Review
    {
        return $this->reviewRepository->findOneBy(
            [
                'mergeRequest' => $comment->getMergeRequest()->getId(),
                'project' => $comment->getProject()->getId(),
            ]
        );
    }

    public function createByComment(Comment $comment): Review
    {
        $review = new Review();

        $review->setProject($comment->getProject());
        $review->setMergeRequest($comment->getMergeRequest());

        $review->addComment($comment);
        $comment->setReview($review);

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $review;
    }

    public function createIfNotExists(Comment $comment): Review
    {
        $review = $this->findByComment($comment);
        if ($review === null) {
            $review = new Review();
        }

        $review->setProject($comment->getProject());
        $review->setMergeRequest($comment->getMergeRequest());
        $comment->setReview($review);

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $review;
    }

    public function notifyAboutReadyReviewsOnComment(Review $review): void
    {
        $numberOfReviewersNeeded = $this->scopeToNumberOfReviewersMapper->mapByScopeName($review->getScope());
        $reviewers = $this->authorService->findReviewers($review, $numberOfReviewersNeeded);
        if (count($reviewers) === 0) {
            $this->logger->warning('Couldn\'t find reviewers', ['review' => $review->getId()]);

            return;
        }

        foreach ($reviewers as $reviewer) {
            $review->addReviewer($reviewer);
            $this->notifyWithErrorLogging($reviewer, $review);
        }
    }

    public function notifyAboutCompletion(Review $review): void
    {
        $scopeName = $review->getScope();
        if (!$scopeName) {
            $this->logger->warning('Scope is not defined for review', ['review' => $review->getId()]);
            return;
        }

        $numberOfReviewers = $this->scopeToNumberOfReviewersMapper->mapByScopeName($scopeName);
        if ($review->getApprovalCount() < $numberOfReviewers) {
            return;
        }

        $review->setStatus(Status::COMPLETED);
        $this->gitlabService->approve($review->getMergeRequest());
        $this->chatService->notifyAboutCompletion($review);
    }

    public function notifyAboutPing(Comment $comment, array $pingedUsernames): void
    {
        if ($comment->getAuthor()->getUsername() === SystemUser::NAME) {
            $this->logger->debug('Skipping ping from the system user');
            return;
        }

        foreach ($pingedUsernames as $pingedUsername) {
            $pingedAuthor = $this->authorService->getAuthorByUsername($pingedUsername);
            if ($pingedAuthor === null) {
                return;
            }

            $this->chatService->notifyAboutPing($comment, $pingedAuthor);
        }

        $this->gitlabService->notifyAboutPing($comment, count($pingedUsernames));
    }

    private function notifyWithErrorLogging($reviewer, Review $review): void
    {
        try {
            $this->gitlabService->notifyAboutReadyReviews($reviewer, $review);
        } catch (Exception $exception) {
            $this->logger->error('Failed to notify via gitlab', ['message' => $exception->getMessage()]);
        }

        try {
            $this->chatService->notifyAboutReadyReviews($reviewer, $review);
        } catch (Exception $exception) {
            $this->logger->error('Failed to notify via slack', ['message' => $exception->getMessage()]);
        }
    }
}
