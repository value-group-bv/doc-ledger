<?php

namespace App\Repository;

use App\Entity\DocSubCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<DocSubCategory> */
class DocSubCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocSubCategory::class);
    }

    /** Returns sub categories for a doc type, scoped to the given subsidiary plus any shared (subsidiary = null) ones. */
    public function findForWizard(int $docTypeId, int $subsidiaryId): array
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.docType = :docTypeId')
            ->andWhere('sc.subsidiary = :subsidiaryId OR sc.subsidiary IS NULL')
            ->setParameter('docTypeId', $docTypeId)
            ->setParameter('subsidiaryId', $subsidiaryId)
            ->orderBy('sc.code', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
