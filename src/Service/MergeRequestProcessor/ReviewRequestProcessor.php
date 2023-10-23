<?php

declare(strict_types=1);

namespace App\Service\MergeRequestProcessor;

use App\Constant\MergeRequest\Action;
use App\Constant\MergeRequest\State;
use App\Constant\Review\Status as ReviewStatus;
use App\Entity\MergeRequest;
use App\Service\ReviewService;
use App\Service\ScopeService;

class ReviewRequestProcessor implements MergeRequestProcessorInterface
{
    private $scopeService;

    private $reviewService;

    public function __construct(
        ScopeService $scopeService,
        ReviewService $reviewService
    ) {
        $this->scopeService = $scopeService;
        $this->reviewService = $reviewService;
    }

    public function supports(MergeRequest $mergeRequest): bool
    {
        return $mergeRequest->getState() === State::OPENED && $mergeRequest->getAction() === Action::OPEN;
    }

    public function process(MergeRequest $mergeRequest): void
    {
        $scopes = $this->scopeService->getAllLongestNameFirst();
        foreach ($scopes as $scope) {
            if (stripos($mergeRequest->getDescription(), $scope->getName()) !== false) {
                $this->processReview($mergeRequest, $scope->getName());

                break;
            }
        }
    }

    protected function processReview(MergeRequest $mergeRequest, string $scopeName): void
    {
        $review = $this->reviewService->findByMergeRequest($mergeRequest);
        if ($review === null) {
            $review = $this->reviewService->createByMergeRequest($mergeRequest);
        }

        $review->setScope($scopeName);
        $review->setStatus(ReviewStatus::IN_REVIEW);

        $this->reviewService->notifyAboutReadyReviewsOnComment($review);
    }
}
