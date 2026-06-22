<?php

namespace App\Repository;

use App\Entity\DocPredefinedNumber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<DocPredefinedNumber> */
class DocPredefinedNumberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocPredefinedNumber::class);
    }
}
