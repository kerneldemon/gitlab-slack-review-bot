<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Author;
use App\Entity\Review;
use Exception;
use JoliCode\Slack\Api\Client;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ChatService
{
    private $client;

    private $logger;

    public function __construct(
        Client $client,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function notifyAboutAdditionalReview(Review $review): void
    {
        $reviewers = $review->getReviewers();
        if ($reviewers->count() === 0) {
            $this->logger->info('No reviewers assigned, not notifying anyone about an additional review');

            return;
        }

        $mergeRequest = $review->getMergeRequest();
        $author = $mergeRequest->getAuthor();
        foreach ($reviewers as $reviewer) {
            $this->postMessage(
                $this->fetchChatUsername($reviewer),
                sprintf(
                    '♻ %s An older MR from %s is once again up for review: %s',
                    $review->getScope(),
                    $author->getUsername(),
                    $mergeRequest->getUrl()
                )
            );
        }
    }

    public function notifyAboutReadyReviews(Author $reviewer, Review $review): void
    {
        $mergeRequest = $review->getMergeRequest();
        $author = $mergeRequest->getAuthor();

        $this->postMessage(
            $this->fetchChatUsername($reviewer),
            sprintf('✨ %s A new MR from %s is up for review: %s', $review->getScope(), $author->getUsername(), $mergeRequest->getUrl())
        );
    }

    public function notifyAboutCompletion(Review $review): void
    {
        $mergeRequest = $review->getMergeRequest();
        $author = $mergeRequest->getAuthor();

        $this->postMessage(
            $this->fetchChatUsername($author),
            sprintf('✔ Your MR has been approved: %s', $mergeRequest->getUrl())
        );
    }

    public function notifyAboutComments(Review $review): void
    {
        $mergeRequest = $review->getMergeRequest();
        $author = $mergeRequest->getAuthor();

        $this->postMessage(
            $this->fetchChatUsername($author),
            sprintf('❌ A reviewer has requested changes: %s', $mergeRequest->getUrl())
        );
    }

    protected function fetchChatUsername(Author $author): string
    {
        $chatUsername = $author->getChatUsername();
        if (!$chatUsername) {
            $this->logger->info('Retrieving user chat username by email');

            $users = null;
            try {
                $users = $this->client->usersLookupByEmail(['email' => $author->getEmail()]);
            } catch (Exception $exception) {
            }

            if ($users === null || !$users->getOk()) {
                throw new RuntimeException('Could not get user by email: ' . $author->getEmail());
            }

            $chatUsername = $users->getUser()->getId();
            $author->setChatUsername($chatUsername);
        }

        return $chatUsername;
    }

    protected function postMessage(string $channel, string $text): void
    {
        try {
            $this->client->chatPostMessage(
                [
                    'channel' => $channel,
                    'text' => $text,
                ]
            );
        } catch (Exception $exception) {
            $this->logger->error('Failed to publish to slack', ['exception' => $exception]);
        }
    }
}
