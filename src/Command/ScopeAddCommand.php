<?php

declare(strict_types=1);

namespace App\Command;

use App\Factory\ScopeFactory;
use App\Repository\ScopeRepository;
use App\Service\ReviewService;
use App\Service\ScopeService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ScopeAddCommand extends Command
{
    private const MANDATORY_PREFIX = '@';

    protected static $defaultName = 'app:scope:add';

    private ScopeFactory $scopeFactory;

    private EntityManagerInterface $entityManager;

    private ScopeRepository $scopeRepository;

    public function __construct(
        ScopeFactory $scopeFactory,
        EntityManagerInterface $entityManager,
        ScopeRepository $scopeRepository
    ) {
        parent::__construct(null);

        $this->scopeFactory = $scopeFactory;
        $this->entityManager = $entityManager;
        $this->scopeRepository = $scopeRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new Question('Please enter scope name:' . PHP_EOL);
        $scopeName = $helper->ask($input, $output, $question);
        if ($scopeName === null) {
            throw new RuntimeException('Scope name cannot be empty');
        }

        $normalizedScopeName = $this->normalizeScope($scopeName);

        $existingScope = $this->scopeRepository->findOneBy(['name' => $normalizedScopeName]);
        if ($existingScope !== null) {
            throw new RuntimeException('Scope already exists');
        }

        $scope = $this->scopeFactory->create($normalizedScopeName);
        $this->entityManager->persist($scope);
        $this->entityManager->flush();

        $output->writeln('Done');

        return Command::SUCCESS;
    }

    private function normalizeScope(string $scopeName): string
    {
        if (strpos($scopeName, self::MANDATORY_PREFIX) === 0) {
            return $scopeName;
        }

        return sprintf('@%s', $scopeName);
    }
}
