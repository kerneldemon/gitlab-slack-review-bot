<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ReviewService;
use App\Service\ScopeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReviewNotifyCommand extends Command
{
    protected static $defaultName = 'app:review:notify';

    private $reviewService;

    private $scopeService;

    public function __construct(
        ReviewService $reviewService,
        ScopeService $scopeService
    ) {
        parent::__construct(null);

        $this->reviewService = $reviewService;
        $this->scopeService = $scopeService;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Processing reviews');

        $scopes = $this->scopeService->getAllLongestNameFirst();
        foreach ($scopes as $scope) {
            $this->reviewService->notifyAboutReadyReviews($scope);
        }

        $output->writeln('Done');

        return Command::SUCCESS;
    }
}
