<?php

namespace App\Repository;

use App\Entity\DocSubsidiary;
use App\Entity\FeasibilityCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FeasibilityCode> */
class FeasibilityCodeRepository extends ServiceEntityRepository
{
    /** Size of the AAA–ZZZ pool (26^3) */
    private const POOL_SIZE = 17576;

    /** Bounded retries for the rare case two requests race for the same free code. */
    private const MAX_ALLOCATE_ATTEMPTS = 5;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeasibilityCode::class);
    }

    /**
     * Allocates the lowest unused AAA–ZZZ code and persists a new feasibility
     * project record for it. Deleting an entry frees its code for reuse.
     * Uniqueness under concurrent requests is enforced by the DB's unique
     * index on `code`, with a retry on collision rather than extra locking.
     */
    public function allocate(DocSubsidiary $subsidiary, string $title, string $requestor): FeasibilityCode
    {
        $em = $this->getEntityManager();

        for ($attempt = 0; $attempt < self::MAX_ALLOCATE_ATTEMPTS; $attempt++) {
            $code = $this->findLowestFreeCode();

            $entry = new FeasibilityCode();
            $entry->setSubsidiary($subsidiary)->setTitle($title)->setRequestor($requestor)->setCode($code);

            try {
                $em->persist($entry);
                $em->flush();

                return $entry;
            } catch (UniqueConstraintViolationException) {
                $em->clear();
            }
        }

        throw new \RuntimeException('Could not allocate a feasibility code after multiple concurrent attempts; please retry.');
    }

    private function findLowestFreeCode(): string
    {
        $usedCodes = array_flip($this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT code FROM feasibility_code WHERE code IS NOT NULL'
        ));

        for ($i = 1; $i <= self::POOL_SIZE; $i++) {
            $candidate = self::codeForIndex($i);
            if (!isset($usedCodes[$candidate])) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Feasibility reference code pool (AAA–ZZZ) is exhausted.');
    }

    /** Most recently created feasibility code across all subsidiaries, or null if none exist yet. */
    public function findLatest(): ?FeasibilityCode
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
