<?php

declare(strict_types=1);

namespace App\Dto\Comment;

class User
{
    private int $id;

    private string $email;

    private string $username;

    public function __construct(
        int $id,
        string $email,
        string $username
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->username = $username;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }
}
