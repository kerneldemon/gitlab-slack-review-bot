<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Scope;
use DateTime;

class ScopeFactory
{
    public function create(string $name): Scope
    {
        $now = new DateTime();
        $scope = new Scope();
        $scope->setCreatedAt($now);
        $scope->setUpdatedAt($now);
        $scope->setName($name);

        return $scope;
    }
}
