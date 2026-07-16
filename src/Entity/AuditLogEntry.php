<?php

namespace App\Entity;

use App\Repository\AuditLogEntryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AuditLogEntryRepository::class)]
#[ORM\Table(name: 'audit_log_entry')]
#[ORM\Index(columns: ['created_at'], name: 'audit_log_entry_created_at_idx')]
#[ORM\Index(columns: ['event'], name: 'audit_log_entry_event_idx')]
class AuditLogEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    /** Snapshotted actor label (user email or API subsidiary code) — not a FK, so entries outlive the actor. */
    #[ORM\Column(length: 255)]
    private string $actor;

    /** Short machine code, e.g. "ledger_entry.updated", "setting.subsidiary.deleted", "auth.login", "api.request". */
    #[ORM\Column(length: 64)]
    private string $event;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $detail = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $actor, string $event, ?string $detail = null)
    {
        $this->actor = $actor;
        $this->event = $event;
        $this->detail = $detail;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getActor(): string
    {
        return $this->actor;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
