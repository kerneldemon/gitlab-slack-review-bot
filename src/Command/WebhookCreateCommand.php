<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\WebhookService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebhookCreateCommand extends Command
{
    protected static $defaultName = 'app:webhook:create';

    private $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        parent::__construct(null);

        $this->webhookService = $webhookService;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->webhookService->createForAllProjects();

        return 0;
    }
}
