<?php

namespace App\Repository;

use App\Entity\MergeRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MergeRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method MergeRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method MergeRequest[]    findAll()
 * @method MergeRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MergeRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MergeRequest::class);
    }
}
