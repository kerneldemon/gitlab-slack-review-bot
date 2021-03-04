<?php

declare(strict_types=1);

namespace App\Service;

use Gitlab\Client as GitlabClient;

class GroupService
{
    private $gitlabClient;

    public function __construct(GitlabClient $gitlabClient)
    {
        $this->gitlabClient = $gitlabClient;
    }

    public function fetchReviewGroups()
    {

    }
}
