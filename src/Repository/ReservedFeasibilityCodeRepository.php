<?php

namespace App\Repository;

use App\Entity\ReservedFeasibilityCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ReservedFeasibilityCode> */
class ReservedFeasibilityCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservedFeasibilityCode::class);
    }
}
