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
            ->leftJoin('e.alternateMainCategories', 'amc')
            ->addSelect('s', 'mc', 'dt', 'sc', 'u', 'amc');

        if ($search) {
            $qb->andWhere(
                'e.title LIKE :search OR e.referenceCode LIKE :search OR e.comments LIKE :search OR ' .
                "CONCAT(s.code, mc.code, '-', e.referenceCode, '-', dt.code, '-', ZEROPAD3(sc.code), '-', ZEROPAD3(e.docNumber)) LIKE :search"
            )->setParameter('search', "%$search%");
        }

        if ($subsidiaryId) {
            $qb->andWhere('s.id = :subsidiaryId')->setParameter('subsidiaryId', $subsidiaryId);
        }

        if ($docTypeId) {
            $qb->andWhere('dt.id = :docTypeId')->setParameter('docTypeId', $docTypeId);
        }

        if ($mainCategoryId) {
            // Also match entries that are valid under this category as an alternate
            $qb->andWhere('mc.id = :mainCategoryId OR :mainCategoryId MEMBER OF e.alternateMainCategories')
               ->setParameter('mainCategoryId', $mainCategoryId);
        }

        $allowedSortFields = ['e.title', 'e.referenceCode', 's.code', 'dt.code', 'mc.code', 'sc.code', 'e.docNumber'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'e.referenceCode';
        }

        $dir = $sortDir === 'ASC' ? 'ASC' : 'DESC';

        if ($sortField === 'e.referenceCode') {
            // Sort by all components that make up the document number
            $qb->orderBy('s.code', $dir)
               ->addOrderBy('mc.code', $dir)
               ->addOrderBy('e.referenceCode', $dir)
               ->addOrderBy('dt.code', $dir)
               ->addOrderBy('sc.code', $dir)
               ->addOrderBy('e.docNumber', $dir);
        } else {
            $qb->orderBy($sortField, $dir);
        }

        return $qb;
    }

    /**
     * Returns the document IDs that would be claimed twice if the given entry were saved:
     * other entries with the same subsidiary, doc type, sub category, number and revision
     * whose allowed main categories (default + alternates) overlap with the given entry's.
     *
     * @return string[]
     */
    public function findConflictingDocumentIds(DocumentEntry $entry): array
    {
        $allowed = $entry->getAllowedMainCategories();

        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.alternateMainCategories', 'amc')
            ->addSelect('amc')
            ->where('e.subsidiary = :subsidiary')
            ->andWhere('e.docType = :docType')
            ->andWhere('e.subCategory = :subCategory')
            ->andWhere('e.docNumber = :docNumber')
            ->andWhere('e.revision = :revision')
            ->setParameter('subsidiary', $entry->getSubsidiary())
            ->setParameter('docType', $entry->getDocType())
            ->setParameter('subCategory', $entry->getSubCategory())
            ->setParameter('docNumber', $entry->getDocNumber())
            ->setParameter('revision', $entry->getRevision());

        if ($entry->getId() !== null) {
            $qb->andWhere('e.id != :id')->setParameter('id', $entry->getId(), 'uuid');
        }

        $conflicts = [];
        foreach ($qb->getQuery()->getResult() as $other) {
            foreach ($other->getAllowedMainCategories() as $mainCategory) {
                if (\in_array($mainCategory, $allowed, true)) {
                    $conflicts[] = $other->getDocumentIdFor($mainCategory);
                }
            }
        }

        return $conflicts;
    }

    /** Returns the highest docNumber used for a given docType + subCategory combination, or null if none exist */
    public function findMaxDocNumber(int $docTypeId, int $subCategoryId): ?int
    {
        $result = $this->createQueryBuilder('e')
            ->select('MAX(e.docNumber)')
            ->where('e.docType = :docTypeId')
            ->andWhere('e.subCategory = :subCategoryId')
            ->setParameter('docTypeId', $docTypeId)
            ->setParameter('subCategoryId', $subCategoryId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (int) $result : null;
    }

    /**
     * Entries whose title contains any of the given lowercase words (substring match, so callers
     * must still check whole words)
     *
     * @param string[] $words
     * @return DocumentEntry[]
     */
    public function findByTitleContainingAny(array $words): array
    {
        if (!$words) {
            return [];
        }

        $qb = $this->createQueryBuilder('e');
        $or = $qb->expr()->orX();
        foreach (array_values($words) as $i => $word) {
            $or->add("LOWER(e.title) LIKE :word$i");
            $qb->setParameter("word$i", "%$word%");
        }

        return $qb->where($or)->getQuery()->getResult();
    }
}
