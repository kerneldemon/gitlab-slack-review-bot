<?php

declare(strict_types=1);

namespace App\Service;

use App\Constant\MergeRequest\State;
use App\Constant\Review\Status;
use App\Entity\MergeRequest;
use App\Repository\MergeRequestRepository;
use Doctrine\ORM\EntityManagerInterface;

class MergeRequestService
{
    private $entityManager;

    private $mergeRequestRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        MergeRequestRepository $mergeRequestRepository
    ) {
        $this->entityManager = $entityManager;
        $this->mergeRequestRepository = $mergeRequestRepository;
    }

    public function processMergeRequest(MergeRequest $mergeRequest): MergeRequest
    {
        if ($mergeRequest->getState() === State::MERGED) {
            $this->updateReviewStatus($mergeRequest);
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
}
