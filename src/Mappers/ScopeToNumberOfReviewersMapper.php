<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Entity\Scope;

class ScopeToNumberOfReviewersMapper
{
    private const DEFAULT_REVIEWER_COUNT = 2;
    private const URGENT_REVIEWER_COUNT = 1;
    private const SMALL_REVIEWER_COUNT = 1;

    public function mapByScope(Scope $scope): int
    {
        $name = $scope->getName();

        return $this->mapByScopeName($name);
    }

    public function mapByScopeName(string $name): int
    {
        if (stripos($name, 'urgent')) {
            return self::URGENT_REVIEWER_COUNT;
        }

        if (stripos($name, 'small')) {
            return self::SMALL_REVIEWER_COUNT;
        }

        return self::DEFAULT_REVIEWER_COUNT;
    }
}
