<?php

namespace App\Twig\Components;

use App\Entity\DocMainCategory;
use App\Entity\DocSubCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocType;
use App\Entity\DocumentEntry;
use App\Repository\DocMainCategoryRepository;
use App\Repository\DocSubCategoryRepository;
use App\Repository\DocSubsidiaryRepository;
use App\Repository\DocTypeRepository;
use App\Repository\DocumentEntryRepository;
use App\Service\TitleCaseFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class DocumentWizard
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public int $subsidiaryId = 0;

    #[LiveProp(writable: true)]
    public int $mainCategoryId = 0;

    #[LiveProp(writable: true)]
    public int $docTypeId = 0;

    #[LiveProp(writable: true)]
    public int $subCategoryId = 0;

    /** 0 = next available, 1 = manual */
    #[LiveProp(writable: true)]
    public int $docNumberMode = 0;

    #[LiveProp(writable: true)]
    public string $manualDocNumber = '';

    /** @var int[] Main category IDs this document is also valid under */
    #[LiveProp(writable: true)]
    public array $alternateMainCategoryIds = [];

    #[LiveProp(writable: true)]
    public string $title = '';

    #[LiveProp(writable: true)]
    public string $comments = '';

    /** Saved document ID string shown after submit */
    #[LiveProp(writable: true)]
    public string $savedDocumentId = '';

    #[LiveProp(writable: true)]
    public string $duplicateError = '';

    public function __construct(
        private readonly DocSubsidiaryRepository $subsidiaries,
        private readonly DocMainCategoryRepository $mainCategories,
        private readonly DocTypeRepository $docTypes,
        private readonly DocSubCategoryRepository $subCategories,
        private readonly DocumentEntryRepository $entries,
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly TitleCaseFormatter $titleCaseFormatter,
        private readonly ValidatorInterface $validator,
    ) {}

    /** @return DocSubsidiary[] */
    public function getSubsidiaries(): array
    {
        return $this->subsidiaries->findBy([], ['sortOrder' => 'ASC']);
    }

    /** @return DocMainCategory[] */
    public function getMainCategories(): array
    {
        return $this->mainCategories->findBy([], ['code' => 'ASC']);
    }

    /** @return DocType[] */
    public function getDocTypes(): array
    {
        return $this->docTypes->findBy([], ['sortOrder' => 'ASC']);
    }

    /** @return DocSubCategory[] — filtered by selected docType, mainCategory and subsidiary */
    public function getSubCategories(): array
    {
        if (!$this->docTypeId || !$this->mainCategoryId || !$this->subsidiaryId) return [];
        return $this->subCategories->findForWizard($this->docTypeId, $this->mainCategoryId, $this->subsidiaryId);
    }

    /**
     * Main categories that can be ticked as alternates: all except the selected default,
     * and only for sub categories that apply to every main category.
     *
     * @return DocMainCategory[]
     */
    public function getAlternateMainCategoryOptions(): array
    {
        if (!$this->mainCategoryId || !$this->subCategoryId) return [];

        $subCategory = $this->em->find(DocSubCategory::class, $this->subCategoryId);
        if (!$subCategory || $subCategory->getMainCategory() !== null) return [];

        return array_values(array_filter(
            $this->getMainCategories(),
            fn(DocMainCategory $mc) => $mc->getId() !== $this->mainCategoryId,
        ));
    }

    /** @return string[] Document IDs the entry would claim that already exist, checked live while filling in the form */
    public function getConflictingDocumentIds(): array
    {
        $entry = $this->buildEntry();
        return $entry ? $this->entries->findConflictingDocumentIds($entry) : [];
    }

    /** @return string[] Document IDs the entry would also be valid as, for the preview */
    public function getAlternatePreviewIds(): array
    {
        $entry = $this->buildEntry();
        if (!$entry) return [];

        return array_map(
            fn(DocMainCategory $mc) => $entry->getDocumentIdFor($mc),
            $entry->getAlternateMainCategories()->toArray(),
        );
    }

    public function getSuggestedDocNumber(): int
    {
        if (!$this->docTypeId || !$this->subCategoryId) return 0;
        $max = $this->entries->findMaxDocNumber($this->docTypeId, $this->subCategoryId);
        return ($max ?? -1) + 1;
    }

    public function getPreviewDocumentId(): string
    {
        return $this->buildEntry()?->getDocumentId() ?? '—';
    }

    /** Builds an unsaved entry from the current selection, or null while the selection is incomplete */
    private function buildEntry(): ?DocumentEntry
    {
        if (!$this->subsidiaryId || !$this->mainCategoryId || !$this->docTypeId || !$this->subCategoryId) {
            return null;
        }

        $subsidiary   = $this->em->find(DocSubsidiary::class, $this->subsidiaryId);
        $mainCategory = $this->em->find(DocMainCategory::class, $this->mainCategoryId);
        $docType      = $this->em->find(DocType::class, $this->docTypeId);
        $subCategory  = $this->em->find(DocSubCategory::class, $this->subCategoryId);

        if (!$subsidiary || !$mainCategory || !$docType || !$subCategory) return null;

        $entry = new DocumentEntry();
        $entry->setSubsidiary($subsidiary);
        $entry->setMainCategory($mainCategory);
        $entry->setReferenceCode($mainCategory->getReferenceCode());
        $entry->setDocType($docType);
        $entry->setSubCategory($subCategory);
        $entry->setDocNumber($this->resolvedDocNumber());
        $entry->setRevision('00');

        // Silently drops stale ticks, e.g. after switching to a scoped sub category
        foreach ($this->getAlternateMainCategoryOptions() as $option) {
            if (\in_array($option->getId(), array_map('intval', $this->alternateMainCategoryIds), true)) {
                $entry->addAlternateMainCategory($option);
            }
        }

        return $entry;
    }

    private function resolvedDocNumber(): int
    {
        if ($this->docNumberMode === 1) {
            return max(0, (int) $this->manualDocNumber);
        }
        return $this->getSuggestedDocNumber();
    }

    #[LiveAction]
    public function resetConfirmation(): void
    {
        $this->savedDocumentId = '';
        $this->duplicateError = '';
    }

    #[LiveAction]
    public function save(): void
    {
        $this->duplicateError = '';

        if (!$this->subsidiaryId || !$this->mainCategoryId || !$this->docTypeId
            || !$this->subCategoryId || !$this->title) {
            return;
        }

        $entry = $this->buildEntry();
        if (!$entry) return;

        $docNumber = $entry->getDocNumber();
        if ($docNumber < 0 || $docNumber > 999) {
            $this->duplicateError = 'Document number must be between 0 and 999.';
            return;
        }

        $violations = $this->validator->validate($entry);
        if (\count($violations) > 0) {
            $this->duplicateError = $violations[0]->getMessage();
            return;
        }

        $entry->setTitle($this->titleCaseFormatter->format($this->title));
        $entry->setComments($this->comments ?: null);
        $entry->setCreatedBy($this->security->getUser());

        $this->em->persist($entry);
        $this->em->flush();

        $this->savedDocumentId = $entry->getDocumentId();

        // Reset form
        $this->subsidiaryId = 0;
        $this->mainCategoryId = 0;
        $this->docTypeId = 0;
        $this->subCategoryId = 0;
        $this->docNumberMode = 0;
        $this->manualDocNumber = '';
        $this->alternateMainCategoryIds = [];
        $this->title = '';
        $this->comments = '';

        $this->emit('entryCreated');
    }
}
