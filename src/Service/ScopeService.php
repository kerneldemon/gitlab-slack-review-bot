<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Scope;
use App\Repository\ScopeRepository;

class ScopeService
{
    private $scopeRepository;

    public function __construct(ScopeRepository $scopeRepository)
    {
        $this->scopeRepository = $scopeRepository;
    }

    /**
     * @return Scope[]|iterable
     */
    public function getAllLongestNameFirst(): iterable
    {
        return $this->scopeRepository->getAllLongestNameFirst();
    }
}
