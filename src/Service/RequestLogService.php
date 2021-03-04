<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\RequestLog;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class RequestLogService
{
    private $entityManager;

    private $isRequestLoggingEnabled;

    public function __construct(
        EntityManagerInterface $entityManager,
        bool $isRequestLoggingEnabled
    ) {
        $this->entityManager = $entityManager;
        $this->isRequestLoggingEnabled = $isRequestLoggingEnabled;
    }

    public function log(string $method, string $headers, string $body): void
    {
        if (!$this->isRequestLoggingEnabled) {
            return;
        }

        $requestLog = new RequestLog();

        $requestLog->setCreatedAt(new DateTime('now'));
        $requestLog->setUpdatedAt(new DateTime('now'));
        $requestLog->setMethod($method);
        $requestLog->setHeaders($headers);
        $requestLog->setBody($body);

        $this->entityManager->persist($requestLog);
        $this->entityManager->flush();
    }
}
