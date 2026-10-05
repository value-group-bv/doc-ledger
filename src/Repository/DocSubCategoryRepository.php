<?php

namespace App\Repository;

use App\Entity\DocSubCategory;
use App\Entity\DocType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<DocSubCategory> */
class DocSubCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocSubCategory::class);
    }

    /** @return DocSubCategory[] All sub categories by code, with their doc types and scope loaded */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('sc')
            ->leftJoin('sc.docTypes', 'dt')
            ->leftJoin('sc.mainCategory', 'mc')
            ->leftJoin('sc.subsidiary', 's')
            ->addSelect('dt', 'mc', 's')
            ->orderBy('sc.code', 'ASC')
            ->addOrderBy('sc.description', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Returns sub categories for a doc type + main category, scoped to the given subsidiary plus shared (null) ones. */
    public function findForWizard(int $docTypeId, int $mainCategoryId, int $subsidiaryId): array
    {
        return $this->createQueryBuilder('sc')
            ->where(':docTypeId MEMBER OF sc.docTypes')
            ->andWhere('sc.mainCategory = :mainCategoryId OR sc.mainCategory IS NULL')
            ->andWhere('sc.subsidiary = :subsidiaryId OR sc.subsidiary IS NULL')
            ->setParameter('docTypeId', $docTypeId)
            ->setParameter('mainCategoryId', $mainCategoryId)
            ->setParameter('subsidiaryId', $subsidiaryId)
            ->orderBy('sc.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Sub categories with the same code and scope that share one of the given doc types — they'd be
     * indistinguishable in the wizard. Replaces a unique constraint, which can't span the join table.
     *
     * @param DocType[] $docTypes
     * @return DocSubCategory[]
     */
    public function findOverlapping(DocSubCategory $subCategory, array $docTypes): array
    {
        $qb = $this->createQueryBuilder('sc')
            ->join('sc.docTypes', 'dt')
            ->where('sc.code = :code')
            ->andWhere('dt IN (:docTypes)')
            ->setParameter('code', $subCategory->getCode())
            ->setParameter('docTypes', $docTypes);

        foreach (['mainCategory' => $subCategory->getMainCategory(), 'subsidiary' => $subCategory->getSubsidiary()] as $field => $value) {
            $value === null
                ? $qb->andWhere("sc.$field IS NULL")
                : $qb->andWhere("sc.$field = :$field")->setParameter($field, $value);
        }
        if ($subCategory->getId() !== null) {
            $qb->andWhere('sc.id != :id')->setParameter('id', $subCategory->getId());
        }

        return $qb->getQuery()->getResult();
    }
}
