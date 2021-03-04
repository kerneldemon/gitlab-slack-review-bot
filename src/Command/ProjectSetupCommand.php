<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Project;
use App\Factory\ProjectFactory;
use App\Repository\ProjectRepository;
use App\Repository\ReviewRepository;
use App\Service\WebhookService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Gitlab\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectSetupCommand extends Command
{
    protected static $defaultName = 'app:project:setup';

    private $client;

    private $webhookService;

    private $projectRepository;

    private $entityManager;

    private $projectFactory;

    private $logger;

    private $reviewRepository;

    public function __construct(
        Client $client,
        WebhookService $webhookService,
        ProjectRepository $projectRepository,
        ReviewRepository $reviewRepository,
        EntityManagerInterface $entityManager,
        ProjectFactory $projectFactory,
        LoggerInterface $logger
    ) {
        parent::__construct(null);

        $this->client = $client;
        $this->webhookService = $webhookService;
        $this->projectRepository = $projectRepository;
        $this->reviewRepository = $reviewRepository;
        $this->entityManager = $entityManager;
        $this->projectFactory = $projectFactory;
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $page = 1;

        do {
            $rawProjects = $this->client->projects()->all(['page' => $page]);
            $this->processRawProjects($rawProjects);

            $page++;
        } while (!empty($rawProjects));

        return 0;
    }

    protected function processRawProjects(array $rawProjects): void
    {
        foreach ($rawProjects as $rawProject) {
            $project = $this->projectRepository->find($rawProject['id']);
            $existingReview = $this->reviewRepository->findOneBy(['project' => $rawProject['id']]);
            if ($project !== null && $existingReview !== null) {
                continue;
            }

            printf('Processing project %s' . PHP_EOL, $rawProject['web_url']);
            if ($project === null) {
                $project = $this->createProject($rawProject);
            }

            $this->createWebhooks($project);
        }

        $this->entityManager->flush();
    }

    private function createProject(array $rawProject): Project
    {
        $project = $this->projectFactory->create($rawProject['id'], $rawProject['web_url']);
        $this->entityManager->persist($project);

        return $project;
    }

    protected function createWebhooks(?Project $project): void
    {
        try {
            $this->webhookService->createForProject($project);
        } catch (Exception $exception) {
            $this->logger->info('Failed to add webhook to project', ['message' => $exception->getMessage()]);
        }
    }
}
