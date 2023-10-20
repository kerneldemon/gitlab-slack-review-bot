<?php

declare(strict_types=1);

namespace App\Service\NoteProcessor;

use App\Constant\Review\Status as ReviewStatus;
use App\Entity\Comment;
use App\Entity\Review;
use App\Service\ChatService;
use App\Service\GitlabService;
use App\Service\ReviewService;
use App\Service\ScopeService;

class ReviewRequestNoteProcessor implements NoteProcessorInterface
{
    private $reviewService;

    private $chatService;

    private $gitlabService;

    private $scopeService;

    public function __construct(
        ReviewService $reviewService,
        ChatService $chatService,
        GitlabService $gitlabService,
        ScopeService $scopeService
    ) {
        $this->reviewService = $reviewService;
        $this->chatService = $chatService;
        $this->gitlabService = $gitlabService;
        $this->scopeService = $scopeService;
    }

    public function supports(Comment $comment): bool
    {
        $scopes = $this->scopeService->getAllLongestNameFirst();
        foreach ($scopes as $scope) {
            if (stripos($comment->getNote(), $scope->getName()) !== false) {
                return true;
            }
        }

        return false;
    }

    public function process(Comment $comment): void
    {
        $scopes = $this->scopeService->getAllLongestNameFirst();
        foreach ($scopes as $scope) {
            if (stripos($comment->getNote(), $scope->getName()) !== false) {
                $this->processReview($comment, $scope->getName());

                break;
            }
        }
    }

    protected function processReview(Comment $comment, string $scopeName): void
    {
        $review = $this->reviewService->findByComment($comment);
        if ($review === null) {
            $review = $this->reviewService->createByComment($comment);
        }

        if ($this->isAdditionalReviewNeeded($review)) {
            $review->setStatus(ReviewStatus::IN_REVIEW);

            $this->gitlabService->unapprove($review->getMergeRequest());
            $this->chatService->notifyAboutAdditionalReview($review);
            $this->gitlabService->notifyAboutAdditionalReview($review);

            return;
        }

        $review->setScope($scopeName);
        $review->setStatus(ReviewStatus::IN_REVIEW);

        $this->reviewService->notifyAboutReadyReviewsOnComment($review);
    }

    protected function isAdditionalReviewNeeded(?Review $review): bool
    {
        return $review->getReviewers()->count() > 0;
    }
}
