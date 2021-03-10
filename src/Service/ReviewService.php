<?php

declare(strict_types=1);

namespace App\Service;

use App\Constant\Review\Status;
use App\Entity\Comment;
use App\Entity\Review;
use App\Entity\Scope;
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
        $comment->setReview($review);

        $this->entityManager->persist($review);

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

        return $review;
    }

    public function notifyAboutReadyReviews(Scope $scope): void
    {
        $numberOfReviewersNeeded = $this->scopeToNumberOfReviewersMapper->mapByScope($scope);

        $reviewInfos = $this->reviewRepository->findReadyReviews($scope, $numberOfReviewersNeeded);
        foreach ($reviewInfos as $reviewInfo) {
            /** @var Review $review */
            $review = $reviewInfo[0];
            $currentReviewerCount = (int) $reviewInfo['reviewerCount'];
            $remainingReviewerCount = $numberOfReviewersNeeded - $currentReviewerCount;
            if ($remainingReviewerCount <= 0) {
                $review->setStatus(Status::CLOSED);
                continue;
            }

            $reviewers = $this->authorService->findReviewers($review, $remainingReviewerCount);
            if (count($reviewers) === 0) {
                continue;
            }

            foreach ($reviewers as $reviewer) {
                $review->addReviewer($reviewer);
                $this->notifyWithErrorLogging($reviewer, $review);
            }
        }

        $this->entityManager->flush();
    }

    public function notifyAboutCompletion(Review $review): void
    {
        $scopeName = $review->getScope();
        $numberOfReviewers = $this->scopeToNumberOfReviewersMapper->mapByScopeName($scopeName);
        if ($review->getApprovalCount() < $numberOfReviewers) {
            return;
        }

        $review->setStatus(Status::COMPLETED);
        $this->chatService->notifyAboutCompletion($review);
    }

    public function notifyAboutComments(Review $review): void
    {
        $this->chatService->notifyAboutComments($review);
        $this->gitlabService->notifyAboutAuthorPing($review);
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
