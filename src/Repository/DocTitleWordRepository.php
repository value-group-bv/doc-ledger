<?php

namespace App\Repository;

use App\Entity\DocTitleWord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<DocTitleWord> */
class DocTitleWordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocTitleWord::class);
    }

    /** @return string[] lowercase words of the given type */
    public function findWordsByType(string $type): array
    {
        return array_map(
            static fn(DocTitleWord $w) => $w->getWord(),
            $this->findBy(['type' => $type], ['word' => 'ASC']),
        );
    }
}
