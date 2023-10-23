<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\MergeRequest;
use App\Repository\MergeRequestRepository;
use App\Service\MergeRequestProcessor\MergeRequestProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;

class MergeRequestService
{
    private $entityManager;

    private $mergeRequestRepository;

    /** @var MergeRequestProcessorInterface[] */
    private $mergeRequestProcessors;

    public function __construct(
        EntityManagerInterface $entityManager,
        MergeRequestRepository $mergeRequestRepository,
        iterable $mergeRequestProcessors
    ) {
        $this->entityManager = $entityManager;
        $this->mergeRequestRepository = $mergeRequestRepository;
        $this->mergeRequestProcessors = $mergeRequestProcessors;
    }

    public function processMergeRequest(MergeRequest $mergeRequest): MergeRequest
    {
        foreach ($this->mergeRequestProcessors as $mergeRequestProcessor) {
            if ($mergeRequestProcessor->supports($mergeRequest)) {
                $mergeRequestProcessor->process($mergeRequest);
                break;
            }
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
}
