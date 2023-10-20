<?php
declare(strict_types=1);

namespace App\Service\MergeRequestProcessor;

use App\Constant\MergeRequest\State;
use App\Constant\Review\Status;
use App\Entity\MergeRequest;

class MergeRequestStatusChangeProcessor implements MergeRequestProcessorInterface
{

    public function supports(MergeRequest $mergeRequest): bool
    {
        return $mergeRequest->getState() === State::MERGED;
    }

    public function process(MergeRequest $mergeRequest): void
    {
        $review = $mergeRequest->getReview();
        if ($review === null) {
            return;
        }

        $review->setStatus(Status::CLOSED);
    }
}