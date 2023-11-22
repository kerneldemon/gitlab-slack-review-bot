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

class ReviewScopeReassignNoteProcessor implements NoteProcessorInterface
{
    private $reviewService;

    private $chatService;

    private $scopeService;

    public function __construct(
        ReviewService $reviewService,
        ChatService $chatService,
        ScopeService $scopeService
    ) {
        $this->reviewService = $reviewService;
        $this->chatService = $chatService;
        $this->scopeService = $scopeService;
    }

    public function supports(Comment $comment): bool
    {
        $review = $this->reviewService->findByComment($comment);

        if ($review === null) {
            return false;
        }

        if (stripos($comment->getNote(), $review->getScope()) !== false) {
            return false;
        }

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
        if ($review->getScope() === $scopeName) {
            return;
        }

        $this->chatService->notifyAboutReassign($review);

        foreach ($review->getReviewers() as $reviewer) {
            $review->removeReviewer($reviewer);
        }

        $review->setScope($scopeName);
    }

    public function preventFurtherProcessing(Comment $comment): bool
    {
        return false;
    }
}
