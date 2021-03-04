<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Project;

class ProjectFactory
{
    public function create(int $id, string $homepage): Project
    {
        $project = new Project();

        $project->setId($id);
        $project->setHomepage($homepage);

        return $project;
    }
}
