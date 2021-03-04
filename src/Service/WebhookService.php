<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class WebhookService
{
    private $projectRepository;

    private $gitlabService;

    private $router;

    private $logger;

    public function __construct(
        ProjectRepository $projectRepository,
        GitlabService $gitlabService,
        RouterInterface $router,
        LoggerInterface $logger
    ) {
        $this->projectRepository = $projectRepository;
        $this->gitlabService = $gitlabService;
        $this->router = $router;
        $this->logger = $logger;
    }

    public function createForAllProjects(): void
    {
        $projects = $this->projectRepository->findAll();

        foreach ($projects as $project) {
            try {
                $this->createForProject($project);
            } catch (Exception $exception) {
                $this->logger->warning('Could re-create webhook', ['project' => $project->getHomepage()]);
            }
        }
    }

    public function createForProject(Project $project)
    {
        $routes = $this->getWebhookRoutes();

        foreach ($routes as $route) {
            $this->deleteOld($project, $route);
            $this->createNew($project, $route);
        }
    }

    private function createNew(Project $project, array $route): void
    {
        $this->gitlabService->createWebhook($project, $route['absolute'], $route['parameters']);
    }

    private function deleteOld(Project $project, array $route)
    {
        $webhooks = $this->gitlabService->fetchWebhooks($project);
        foreach ($webhooks as $webhook) {
            if (stripos($webhook['url'], $route['relative']) === false) {
                continue;
            }

            $this->gitlabService->deleteWebhook($project, $webhook['id']);
        }
    }

    private function getWebhookRoutes(): array
    {
        return [
            [
                'absolute' => $this->router->generate('app_handle_event_comment', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'relative' => $this->router->generate('app_handle_event_comment', [], UrlGeneratorInterface::RELATIVE_PATH),
                'parameters' => [
                    'note_events' => true,
                    'push_events' => false,
                ],
            ],
            [
                'absolute' => $this->router->generate('app_handle_event_merge_request', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'relative' => $this->router->generate('app_handle_event_merge_request', [], UrlGeneratorInterface::RELATIVE_PATH),
                'parameters' => [
                    'merge_requests_events' => true,
                    'push_events' => false,
                ],
            ],
        ];
    }
}
