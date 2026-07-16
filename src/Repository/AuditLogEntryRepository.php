<?php

namespace App\Repository;

use App\Entity\AuditLogEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLogEntry>
 */
class AuditLogEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLogEntry::class);
    }

    public function createFilteredQueryBuilder(?string $event = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')->orderBy('a.createdAt', 'DESC');

        if ($event) {
            $qb->andWhere('a.event = :event')->setParameter('event', $event);
        }

        return $qb;
    }

    /** @return string[] */
    public function findDistinctEvents(): array
    {
        return array_column(
            $this->createQueryBuilder('a')
                ->select('DISTINCT a.event')
                ->orderBy('a.event', 'ASC')
                ->getQuery()
                ->getScalarResult(),
            'event'
        );
    }
}
