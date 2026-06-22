<?php

namespace App\Repository;

use App\Entity\DocumentEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<DocumentEntry> */
class DocumentEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentEntry::class);
    }

    public function createFilteredQueryBuilder(
        ?string $search = null,
        ?int $subsidiaryId = null,
        ?int $docTypeId = null,
        ?int $mainCategoryId = null,
        string $sortField = 'e.createdAt',
        string $sortDir = 'DESC'
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.subsidiary', 's')
            ->leftJoin('e.mainCategory', 'mc')
            ->leftJoin('e.docType', 'dt')
            ->leftJoin('e.subCategory', 'sc')
            ->leftJoin('e.createdBy', 'u')
            ->addSelect('s', 'mc', 'dt', 'sc', 'u');

        if ($search) {
            $qb->andWhere('e.title LIKE :search OR e.referenceCode LIKE :search')
               ->setParameter('search', "%$search%");
        }

        if ($subsidiaryId) {
            $qb->andWhere('s.id = :subsidiaryId')->setParameter('subsidiaryId', $subsidiaryId);
        }

        if ($docTypeId) {
            $qb->andWhere('dt.id = :docTypeId')->setParameter('docTypeId', $docTypeId);
        }

        if ($mainCategoryId) {
            $qb->andWhere('mc.id = :mainCategoryId')->setParameter('mainCategoryId', $mainCategoryId);
        }

        $allowedSortFields = ['e.title', 'e.referenceCode', 's.code', 'dt.code', 'mc.code', 'sc.code', 'e.docNumber'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'e.referenceCode';
        }

        $qb->orderBy($sortField, $sortDir === 'ASC' ? 'ASC' : 'DESC');

        return $qb;
    }

    /** Returns the highest docNumber used for a given docType + subCategory combination */
    public function findMaxDocNumber(int $docTypeId, int $subCategoryId): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('MAX(e.docNumber)')
            ->where('e.docType = :docTypeId')
            ->andWhere('e.subCategory = :subCategoryId')
            ->setParameter('docTypeId', $docTypeId)
            ->setParameter('subCategoryId', $subCategoryId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }
}
