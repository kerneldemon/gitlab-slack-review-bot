<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\RequestLogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestLogEventListener implements EventSubscriberInterface
{
    private $requestLogService;

    public function __construct(RequestLogService $requestLogService)
    {
        $this->requestLogService = $requestLogService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['logRequest', 0],
            ],
        ];
    }

    public function logRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $method = $request->getMethod();
        $headers = json_encode($request->headers->all());
        $body = json_encode($request->getContent());

        $this->requestLogService->log($method, $headers, $body);
    }

}
