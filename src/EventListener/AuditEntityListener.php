<?php

namespace App\EventListener;

use App\Entity\DocMainCategory;
use App\Entity\DocSubCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocType;
use App\Entity\DocumentEntry;
use App\Entity\FeasibilityCode;
use App\Entity\User;
use App\Service\AuditLogger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Logs create/update/delete of ledger entries and admin-managed settings to the audit log.
 * Only fires for changes made by an authenticated web user — CLI commands (seeding, imports)
 * and unauthenticated flows (e.g. auto-provisioning a user on first SSO login) stay silent.
 */
#[AsDoctrineListener(event: Events::onFlush)]
class AuditEntityListener
{
    private const LEDGER_ENTRY_CLASSES = [
        DocumentEntry::class,
    ];

    /**
     * DocTitleWord is deliberately excluded: AdminController::titleWordsUpdate() replaces the
     * whole word list on every save (delete-all + re-insert), which would otherwise produce one
     * audit row per word. That action logs a single summarized entry itself instead.
     */
    private const SETTING_CLASSES = [
        DocSubsidiary::class,
        DocMainCategory::class,
        DocSubCategory::class,
        DocType::class,
        FeasibilityCode::class,
        User::class,
    ];

    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly Security $security,
    ) {}

    public function onFlush(OnFlushEventArgs $args): void
    {
        $actor = $this->security->getUser();
        if (!$actor instanceof User) {
            return;
        }

        $uow = $args->getObjectManager()->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->recordChange($actor, $entity, 'created');
        }
        // Collection-only changes (e.g. ticking an alternate main category) don't schedule an
        // entity update, so fold them into the owner's list of changed fields.
        $updates = [];
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $updates[spl_object_id($entity)] = [$entity, array_keys($uow->getEntityChangeSet($entity))];
        }
        foreach ([...$uow->getScheduledCollectionUpdates(), ...$uow->getScheduledCollectionDeletions()] as $collection) {
            $owner = $collection->getOwner();
            if ($owner === null || $uow->isScheduledForInsert($owner) || $uow->isScheduledForDelete($owner)) {
                continue;
            }
            $updates[spl_object_id($owner)] ??= [$owner, []];
            $updates[spl_object_id($owner)][1][] = $collection->getMapping()->fieldName;
        }
        foreach ($updates as [$entity, $changedFields]) {
            $this->recordChange($actor, $entity, 'updated', array_unique($changedFields));
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->recordChange($actor, $entity, 'deleted');
        }
    }

    /** @param string[] $changedFields */
    private function recordChange(User $actor, object $entity, string $action, array $changedFields = []): void
    {
        $category = match (true) {
            \in_array($entity::class, self::LEDGER_ENTRY_CLASSES, true) => 'ledger_entry',
            \in_array($entity::class, self::SETTING_CLASSES, true) => 'setting',
            default => null,
        };

        if ($category === null) {
            return;
        }

        $label = $this->describe($entity);
        $detail = match ($action) {
            'created' => "Created {$label}",
            'deleted' => "Deleted {$label}",
            'updated' => "Updated {$label}" . ($changedFields ? ' (' . implode(', ', $changedFields) . ')' : ''),
        };

        $this->auditLogger->logDuringFlush($actor->getEmail(), "{$category}.{$action}", $detail);
    }

    private function describe(object $entity): string
    {
        $shortName = (new \ReflectionClass($entity))->getShortName();

        foreach (['getDocumentId', 'getCode', 'getWord', 'getEmail'] as $getter) {
            if (!method_exists($entity, $getter)) {
                continue;
            }
            $value = $entity->$getter();
            if ($value !== null && $value !== '') {
                return "{$shortName} {$value}";
            }
        }

        return $shortName;
    }
}
