<?php

declare(strict_types=1);

namespace App\Service;

use App\Constant\MergeRequest\Action;
use App\Constant\MergeRequest\State;
use App\Constant\Review\Status;
use App\Entity\MergeRequest;
use App\Entity\Scope;
use App\Repository\MergeRequestRepository;
use Doctrine\ORM\EntityManagerInterface;

class MergeRequestService
{
    private $entityManager;

    private $mergeRequestRepository;

    private $scopeService;

    private $gitlabService;

    public function __construct(
        EntityManagerInterface $entityManager,
        MergeRequestRepository $mergeRequestRepository,
        ScopeService $scopeService,
        GitlabService $gitlabService
    ) {
        $this->entityManager = $entityManager;
        $this->mergeRequestRepository = $mergeRequestRepository;
        $this->scopeService = $scopeService;
        $this->gitlabService = $gitlabService;
    }

    public function processMergeRequest(MergeRequest $mergeRequest): MergeRequest
    {
        if ($mergeRequest->getState() === State::MERGED) {
            $this->updateReviewStatus($mergeRequest);
        }

        if ($mergeRequest->getState() === State::OPENED && $mergeRequest->getAction() === Action::OPEN) {
            $this->processNewMergeRequest($mergeRequest);
        }

        $this->entityManager->flush();

        return $mergeRequest;
    }

    /**
     * @param string $reviewStatus
     * @return iterable|MergeRequest[]
     */
    public function findByReviewStatus(string $reviewStatus): iterable
    {
        return $this->mergeRequestRepository->findByReviewStatus($reviewStatus);
    }

    protected function updateReviewStatus(MergeRequest $mergeRequest): void
    {
        $review = $mergeRequest->getReview();
        if ($review === null) {
            return;
        }

        $review->setStatus(Status::CLOSED);
    }

    private function processNewMergeRequest(MergeRequest $mergeRequest)
    {
        $requestedScope = $this->findScopeFromDescription($mergeRequest);
        if ($requestedScope !== null) {
            $this->gitlabService->notifyAboutInitialReviewRequest($mergeRequest, $requestedScope);
        }
    }

    private function findScopeFromDescription(MergeRequest $mergeRequest): ?Scope
    {
        $scopes = $this->scopeService->getAllLongestNameFirst();
        foreach ($scopes as $scope) {
            if (stripos($mergeRequest->getDescription(), $scope->getName()) !== false) {
                return $scope;
            }
        }

        return null;
    }
}
