<?php

namespace App\Repository;

use App\Constant\Gitlab\SystemUser;
use App\Entity\Author;
use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Author|null find($id, $lockMode = null, $lockVersion = null)
 * @method Author|null findOneBy(array $criteria, array $orderBy = null)
 * @method Author[]    findAll()
 * @method Author[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Author::class);
    }

    public function findReviewers(Review $review, int $limit, array $allowedAuthorIds = []): array
    {
        $ignoredAuthorIds = $this->getAuthorsThatNeedToBeIgnored($review);
        $potentialAuthors = $this->fetchPotentialAuthors($allowedAuthorIds, $ignoredAuthorIds);

        return array_slice($potentialAuthors, 0, $limit);
    }

    private function getAuthorsThatNeedToBeIgnored(Review $review): array
    {
        $ignoredAuthors = [];

        $systemAuthor = $this->findOneBy(['username' => SystemUser::NAME]);
        $mergeRequestAuthor = $review->getMergeRequest()->getAuthor();

        $blacklistedAuthors = $this->createQueryBuilder('a')
            ->leftJoin('a.authorBlacklist', 'ab')
            ->where('ab is NOT NULL')
            ->getQuery()
            ->getResult()
        ;

        array_push(
            $ignoredAuthors,
            $systemAuthor,
            $mergeRequestAuthor,
            ...$blacklistedAuthors,
            ...$review->getReviewers()
        );

        $ignoredAuthorIds = array_map(
            static function (Author $author) {
                return $author->getId();
            },
            $ignoredAuthors
        );

        return array_unique($ignoredAuthorIds);
    }

    private function fetchPotentialAuthors(array $allowedAuthorIds, array $ignoredAuthorIds)
    {
        $queryBuilder = $this->createQueryBuilder('a');
        if (!empty($ignoredAuthorIds)) {
            $queryBuilder = $queryBuilder
                ->andWhere('a.id NOT IN (:ignoredAuthorIds)')
                ->setParameter('ignoredAuthorIds', $ignoredAuthorIds);
        }

        return $queryBuilder
            ->addSelect('RAND() as HIDDEN rand')
            ->andWhere('a.id IN (:allowedAuthorIds)')
            ->setParameter('allowedAuthorIds', $allowedAuthorIds)
            ->orderBy('rand()')
            ->getQuery()
            ->getResult();
    }

    public function findAllNotAlreadyBlacklisted(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.authorBlacklist', 'ab')
            ->andWhere('ab is NULL')
            ->groupBy('a')
            ->getQuery()
            ->getResult();
    }
}
