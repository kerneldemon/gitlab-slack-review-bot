<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableEntity;
use App\Repository\RequestLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RequestLogRepository::class)
 */
class RequestLog
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $method;

    /**
     * @ORM\Column(type="text")
     */
    private $headers;

    /**
     * @ORM\Column(type="text")
     */
    private $body;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getHeaders(): ?string
    {
        return $this->headers;
    }

    public function setHeaders(string $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }
}
