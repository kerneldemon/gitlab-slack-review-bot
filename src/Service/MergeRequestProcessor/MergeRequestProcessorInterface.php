<?php

declare(strict_types=1);

namespace App\Service\MergeRequestProcessor;

use App\Entity\MergeRequest;

interface MergeRequestProcessorInterface
{
    public function supports(MergeRequest $mergeRequest): bool;

    public function process(MergeRequest $mergeRequest): void;
}
