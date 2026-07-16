<?php

namespace App\Service;

use App\Entity\AuditLogEntry;
use Doctrine\ORM\EntityManagerInterface;

class AuditLogger
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function log(string $actor, string $event, ?string $detail = null): void
    {
        $this->em->persist(new AuditLogEntry($actor, $event, $detail));
        $this->em->flush();
    }

    /**
     * For use from inside a Doctrine onFlush listener, where flush() can't be called again —
     * schedules the entry for insertion within the flush that's already in progress.
     */
    public function logDuringFlush(string $actor, string $event, ?string $detail = null): void
    {
        $entry = new AuditLogEntry($actor, $event, $detail);
        $this->em->persist($entry);

        $uow = $this->em->getUnitOfWork();
        $uow->computeChangeSet($this->em->getClassMetadata(AuditLogEntry::class), $entry);
    }
}
