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
use JoliCode\Slack\Exception\SlackErrorResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AuthorBlacklistSyncCommand extends Command
{
    private const STATUS_WHITELISTED = 'whitelisted';
    private const STATUS_BLACKLISTED = 'blacklisted';
    private const STATUS_BANNED = 'banned';

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
            if ($blacklistedAuthor->isBanned()) {
                continue;
            }

            $author = $blacklistedAuthor->getAuthor();
            if (!$author || $this->isAuthorIgnored($author)) {
                continue;
            }

            $authorStatusInBlacklist = $this->fetchAuthorStatusInBlacklist($author);
            if ($authorStatusInBlacklist === self::STATUS_BANNED) {
                $blacklistedAuthor->setBanned(true);
                continue;
            }

            if ($authorStatusInBlacklist === self::STATUS_BLACKLISTED) {
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

            $authorStatusInBlacklist = $this->fetchAuthorStatusInBlacklist($author);
            if ($authorStatusInBlacklist === self::STATUS_WHITELISTED) {
                continue;
            }

            $authorBlacklist = new AuthorBlacklist();
            $authorBlacklist->setAuthor($author);
            $authorBlacklist->setBanned($authorStatusInBlacklist === self::STATUS_BANNED);

            $this->entityManager->persist($authorBlacklist);
        }
    }

    protected function fetchAuthorStatusInBlacklist(Author $author): string
    {
        if ($author->getEmail() === null) {
            return self::STATUS_WHITELISTED;
        }

        try {
            $slackUserResponse = $this->client->usersLookupByEmail(['email' => $author->getEmail()]);
        } catch (SlackErrorResponse $slackErrorResponse) {
            if ($slackErrorResponse->getErrorCode() === 'users_not_found') {
                return self::STATUS_BANNED;
            }

            $this->logger->error('Slack error response received', [
                'email' => $author->getEmail(),
                'response' => $slackErrorResponse->getMessage(),
            ]);

            return self::STATUS_WHITELISTED;
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage(), ['email' => $author->getEmail()]);

            return self::STATUS_WHITELISTED;
        }

        if (!$slackUserResponse || !$slackUserResponse->getOk()) {
            $this->logger->warning('Slack fetch by email not ok', ['response' => $slackUserResponse]);

            return self::STATUS_WHITELISTED;
        }

        $slackUser = $slackUserResponse->getUser();
        if (!$slackUser) {
            $this->logger->warning('Could not fetch slack user from response');

            return self::STATUS_WHITELISTED;
        }

        $profile = $slackUser->getProfile();
        if (!$profile) {
            $this->logger->warning('Could not fetch slack profile from response');

            return self::STATUS_WHITELISTED;
        }

        $hasBlacklistedEmoji = in_array(
            $profile->getStatusEmoji(),
            self::BLACKLIST_EMOJIS,
            true
        );

        return $hasBlacklistedEmoji ? self::STATUS_BLACKLISTED : self::STATUS_WHITELISTED;
    }

    protected function isAuthorIgnored(Author $author): bool
    {
        return in_array($author->getUsername(), self::IGNORED_USERNAMES, true);
    }
}
