<?php

declare(strict_types=1);

namespace App\Command;

use App\Constant\Gitlab\SystemUser;
use App\Entity\Author;
use App\Entity\AuthorBlacklist;
use App\Repository\AuthorBlacklistRepository;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JoliCode\Slack\Api\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AuthorBlacklistSyncCommand extends Command
{
    private const IGNORED_USERNAMES = [
        SystemUser::NAME,
    ];

    private const BLACKLIST_EMOJIS = [
        ':face_with_thermometer:',
        ':palm_tree:',
    ];

    protected static $defaultName = 'app:blacklist:sync';

    private $client;

    private $authorBlacklistRepository;

    private $entityManager;

    private $authorRepository;

    private $logger;

    public function __construct(
        Client $client,
        AuthorBlacklistRepository $authorBlacklistRepository,
        AuthorRepository $authorRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        parent::__construct(null);

        $this->client = $client;
        $this->authorBlacklistRepository = $authorBlacklistRepository;
        $this->authorRepository = $authorRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->removeStaleBlacklists();

        $this->fillBlacklistFromSlackEmojis();

        $this->entityManager->flush();

        return 0;
    }

    protected function removeStaleBlacklists(): void
    {
        $blacklistedAuthors = $this->authorBlacklistRepository->findAll();
        foreach ($blacklistedAuthors as $blacklistedAuthor) {
            $author = $blacklistedAuthor->getAuthor();
            if ($this->isAuthorIgnored($author)) {
                continue;
            }

            if ($this->isAuthorBlacklistedInSlack($author)) {
                continue;
            }

            $this->entityManager->remove($blacklistedAuthor);
        }
    }

    protected function fillBlacklistFromSlackEmojis(): void
    {
        $authors = $this->authorRepository->findAllNotAlreadyBlacklisted();
        foreach ($authors as $author) {
            if ($this->isAuthorIgnored($author)) {
                continue;
            }

            if (!$this->isAuthorBlacklistedInSlack($author)) {
                continue;
            }

            $authorBlacklist = new AuthorBlacklist();
            $authorBlacklist->setAuthor($author);

            $this->entityManager->persist($authorBlacklist);
        }
    }

    protected function isAuthorBlacklistedInSlack(Author $author): bool
    {
        if ($author->getEmail() === null) {
            return false;
        }

        try {
            $slackUserResponse = $this->client->usersLookupByEmail(['email' => $author->getEmail()]);
        } catch (Exception $exception) {
            $this->logger->warning($exception->getMessage(), ['email' => $author->getEmail()]);
            return false;
        }

        if (!$slackUserResponse->getOk()) {
            return false;
        }

        $profile = $slackUserResponse->getUser()->getProfile();

        return in_array($profile->getStatusEmoji(), self::BLACKLIST_EMOJIS, true);
    }

    protected function isAuthorIgnored(Author $author): bool
    {
        return in_array($author->getUsername(), self::IGNORED_USERNAMES, true);
    }
}
