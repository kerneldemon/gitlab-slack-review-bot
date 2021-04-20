<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Author;
use App\Entity\Comment;
use App\Entity\Review;
use App\Factory\AuthorFactory;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AuthorService
{
    private const AUTHOR_REGEX = '#@(\S*)#';

    private $logger;

    private $authorRepository;

    private $gitlabService;

    private $authorFactory;

    private $entityManager;

    public function __construct(
        LoggerInterface $logger,
        AuthorRepository $authorRepository,
        GitlabService $gitlabService,
        AuthorFactory $authorFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $logger;
        $this->authorRepository = $authorRepository;
        $this->gitlabService = $gitlabService;
        $this->authorFactory = $authorFactory;
        $this->entityManager = $entityManager;
    }

    public function findReviewers(Review $review, int $limit): array
    {
        $manuallyTaggedReviewerIds = $this->fetchReviewerIdsByReviewComment($review);
        $groupReviewerIds = $this->fetchReviewerIdsByScope($review->getScope());

        return $this->authorRepository->findReviewers(
            $review,
            $limit,
            $groupReviewerIds,
            $manuallyTaggedReviewerIds
        );
    }

    protected function fetchReviewerIdsByReviewComment(Review $review): array
    {
        /** @var Comment $comment */
        $comment = $review->getComments()->first();
        $note = str_replace($review->getScope(), '', $comment->getNote());
        $reviewerTagCount = preg_match_all(self::AUTHOR_REGEX, $note, $matches);
        if ($reviewerTagCount === 0) {
            return [];
        }

        $reviewerUsernames = $matches[1];
        $reviewerIds = [];

        foreach ($reviewerUsernames as $reviewerUsername) {
            $reviewer = $this->gitlabService->fetchMembersByUsername($reviewerUsername);
            if ($reviewer === null) {
                continue;
            }

            $reviewerIds[] = $reviewer['id'];
        }

        return $reviewerIds;
    }

    protected function fetchReviewerIdsByScope(string $scope)
    {
        $group = $this->fetchGroupByScope($scope);
        if ($group === null) {
            $this->logger->error('Could not find group by scope', ['scope' => $scope]);
            return [];
        }

        $members = $this->gitlabService->fetchMembersByGroupId($group['id']);

        return array_column($members, 'id');
    }

    public function syncReviewersByScope(string $scope)
    {
        $group = $this->fetchGroupByScope($scope);
        if ($group === null) {
            $this->logger->error('Could not find group by scope', ['scope' => $scope]);
            return;
        }

        $rawAuthors = $this->gitlabService->fetchMembersByGroupId($group['id']);
        foreach ($rawAuthors as $rawAuthor) {
            $existingAuthor = $this->authorRepository->find($rawAuthor['id']);
            if ($existingAuthor !== null) {
                continue;
            }

            $author = $this->authorFactory->create($rawAuthor['id'], $rawAuthor['username']);
            $this->entityManager->persist($author);
        }

        $this->entityManager->flush();
    }

    protected function fetchGroupByScope(string $scope)
    {
        $groupName = str_replace('@', '', $scope);
        $subGroupName = substr($scope, strrpos($scope, '/') + 1);

        $subgroups = $this->gitlabService->findAllGroupsByName($subGroupName);
        foreach ($subgroups as $subgroup) {
            if ($subgroup['full_path'] !== $groupName) {
                continue;
            }

            return $subgroup;
        }

        return null;
    }

    public function getAuthorByUsername(string $username): ?Author
    {
        return $this->authorRepository->findOneBy(['username' => $username]);
    }
}
