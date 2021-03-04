<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Author;

class AuthorFactory
{
    public function create(
        int $id,
        string $username
    ) {
        $author = new Author();
        $author->setId($id);
        $author->setUsername($username);

        return $author;
    }
}
