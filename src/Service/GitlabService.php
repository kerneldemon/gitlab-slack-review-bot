<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Author;
use App\Entity\Comment;
use App\Entity\MergeRequest;
use App\Entity\Project;
use App\Entity\Review;
use Exception;
use Gitlab\Client;
use Psr\Log\LoggerInterface;

class GitlabService
{
    private $client;

    private $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function notifyAboutAdditionalReview(Review $review): void
    {
        $mergeRequest = $review->getMergeRequest();
        $project = $mergeRequest->getProject();

        $body = 'I\'ve notified all of the reviewers through slack to review this MR again';

        try {
            $this->client->mergeRequests()->addNote($project->getId(), $mergeRequest->getIid(), $body);
        } catch (Exception $exception) {
            $this->logger->error('Failed to notify gitlab, system user does not have permissions', ['exception' => $exception]);
        }
    }

    public function notifyAboutPing(Comment $comment, int $pingedUsernamesCount): void
    {
        $mergeRequest = $comment->getMergeRequest();
        $project = $mergeRequest->getProject();

        $body = 'I\'ve notified the person you pinged directly';
        if ($pingedUsernamesCount > 1) {
            $body = 'I\'ve notified the people you pinged';
        }

        try {
            $this->client->mergeRequests()->addNote($project->getId(), $mergeRequest->getIid(), $body);
        } catch (Exception $exception) {
            $this->logger->error('Failed to notify gitlab, system user does not have permissions', ['exception' => $exception]);
        }
    }

    public function notifyAboutReadyReviews(Author $author, Review $review): void
    {
        $mergeRequest = $review->getMergeRequest();
        $project = $mergeRequest->getProject();
        $username = $author->getUsername();

        $body = sprintf('@%s, please review this merge request', $username);

        try {
            $this->client->mergeRequests()->addNote($project->getId(), $mergeRequest->getIid(), $body);
        } catch (Exception $exception) {
            $this->logger->error('Failed to notify gitlab, system user does not have permissions', ['exception' => $exception]);
        }
    }

    public function createWebhook(Project $project, string $url, array $parameters): void
    {
        $this->client->projects()->addHook($project->getId(), $url, $parameters);
    }

    public function fetchWebhooks(Project $project): array
    {
        return $this->client->projects()->hooks($project->getId());
    }

    public function deleteWebhook(Project $project, int $hookId): void
    {
        $this->client->projects()->removeHook($project->getId(), $hookId);
    }

    public function fetchMembersByGroupId(int $groupId)
    {
        return $this->client->groups()->members($groupId);
    }

    public function fetchMembersByUsername(string $username): ?array
    {
        $userList = $this->client->users()->all(['username' => $username]);
        if (empty($userList)) {
            return null;
        }

        return $userList[0];
    }

    public function findAllGroupsByName(string $groupName): iterable
    {

        $page = 1;

        do {
            $groups = $this->client->groups()->all(
                [
                    'search' => $groupName,
                    'page' => $page,
                ]
            );

            if (empty($groups)) {
                break;
            }

            yield from $groups;
            $page++;
        } while (true);
    }

    public function approve(MergeRequest $mergeRequest)
    {
        try {
            $this->client->mergeRequests()->approve($mergeRequest->getProject()->getId(), $mergeRequest->getIid());
        } catch (Exception $exception) {
            $this->logger->error('Failed to notify gitlab, system user does not have permissions', ['exception' => $exception]);
        }
    }

    public function unapprove(MergeRequest $mergeRequest)
    {
        try {
            $this->client->mergeRequests()->unapprove($mergeRequest->getProject()->getId(), $mergeRequest->getIid());
        } catch (Exception $exception) {
            $this->logger->error('Failed to notify gitlab, system user does not have permissions', ['exception' => $exception]);
        }
    }
}
