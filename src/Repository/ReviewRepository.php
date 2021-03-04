<?php

namespace App\Repository;

use App\Constant\Review\Status;
use App\Entity\Review;
use App\Entity\Scope;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Review|null find($id, $lockMode = null, $lockVersion = null)
 * @method Review|null findOneBy(array $criteria, array $orderBy = null)
 * @method Review[]    findAll()
 * @method Review[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @param string $reviewStatus
     * @return iterable|Review[]
     */
    public function findReadyReviews(Scope $scope, int $neededNumberOfReviewers): iterable
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.reviewers', 'rs')
            ->andWhere('r.scope = :scope')
            ->andWhere('r.status = :status')
            ->setParameter('scope', $scope->getName())
            ->setParameter('status', Status::IN_REVIEW)
            ->orderBy('r.id', 'DESC')
            ->addSelect('COUNT(rs) as reviewerCount')
            ->having('reviewerCount < :neededNumberOfReviewers')
            ->setParameter('neededNumberOfReviewers', $neededNumberOfReviewers)
            ->groupBy('r')
            ->getQuery()
            ->getResult();
    }
}
