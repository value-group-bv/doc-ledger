<?php

namespace App\Repository;

use App\Entity\DocSubsidiary;
use App\Entity\FeasibilityCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FeasibilityCode> */
class FeasibilityCodeRepository extends ServiceEntityRepository
{
    /** Size of the AAA–ZZZ pool (26^3) */
    private const POOL_SIZE = 17576;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeasibilityCode::class);
    }

    /**
     * Allocates the next unused AAA–ZZZ code and persists a new feasibility
     * project record for it. The code is derived from the row's own
     * autoincrement id, so allocation is race-safe without extra locking.
     */
    public function allocate(DocSubsidiary $subsidiary, string $title, string $requestor): FeasibilityCode
    {
        $entry = new FeasibilityCode();
        $entry->setSubsidiary($subsidiary)->setTitle($title)->setRequestor($requestor);

        $em = $this->getEntityManager();
        $em->persist($entry);
        $em->flush();

        $index = $entry->getId();
        if ($index > self::POOL_SIZE) {
            throw new \RuntimeException('Feasibility reference code pool (AAA–ZZZ) is exhausted.');
        }

        $entry->setCode(self::codeForIndex($index));
        $em->flush();

        return $entry;
    }

    /** @return FeasibilityCode[] */
    public function findRecent(int $limit): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return FeasibilityCode[] */
    public function findAllOrderedByCreatedAt(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Converts a 1-based index into a 3-letter code: 1 => AAA, 2 => AAB, … 17576 => ZZZ */
    private static function codeForIndex(int $index): string
    {
        $zeroBased = $index - 1;

        $first = intdiv($zeroBased, 676) % 26;
        $second = intdiv($zeroBased, 26) % 26;
        $third = $zeroBased % 26;

        return chr(65 + $first) . chr(65 + $second) . chr(65 + $third);
    }
}
