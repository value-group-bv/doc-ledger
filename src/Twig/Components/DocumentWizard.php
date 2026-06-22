<?php

namespace App\Twig\Components;

use App\Entity\DocMainCategory;
use App\Entity\DocPredefinedNumber;
use App\Entity\DocSubCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocType;
use App\Entity\DocumentEntry;
use App\Repository\DocMainCategoryRepository;
use App\Repository\DocPredefinedNumberRepository;
use App\Repository\DocSubCategoryRepository;
use App\Repository\DocSubsidiaryRepository;
use App\Repository\DocTypeRepository;
use App\Repository\DocumentEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
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

    /** 0 = use predefined, 1 = manual */
    #[LiveProp(writable: true)]
    public int $docNumberMode = 0;

    #[LiveProp(writable: true)]
    public int $predefinedNumberId = 0;

    #[LiveProp(writable: true)]
    public string $manualDocNumber = '';

    #[LiveProp(writable: true)]
    public string $title = '';

    /** Saved document number string shown after submit */
    #[LiveProp(writable: true)]
    public string $savedDocNumber = '';

    public function __construct(
        private readonly DocSubsidiaryRepository $subsidiaries,
        private readonly DocMainCategoryRepository $mainCategories,
        private readonly DocTypeRepository $docTypes,
        private readonly DocSubCategoryRepository $subCategories,
        private readonly DocPredefinedNumberRepository $predefinedNumbers,
        private readonly DocumentEntryRepository $entries,
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
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

    /** @return DocPredefinedNumber[] — filtered by selected subCategory */
    public function getPredefinedNumbers(): array
    {
        if (!$this->subCategoryId) return [];
        return $this->predefinedNumbers->findBy(['subCategory' => $this->subCategoryId], ['code' => 'ASC']);
    }

    public function getSuggestedDocNumber(): int
    {
        if (!$this->docTypeId || !$this->subCategoryId) return 0;
        return $this->entries->findMaxDocNumber($this->docTypeId, $this->subCategoryId) + 1;
    }

    public function getPreviewDocNumber(): string
    {
        if (!$this->subsidiaryId || !$this->mainCategoryId || !$this->docTypeId || !$this->subCategoryId) {
            return '—';
        }

        $subsidiary   = $this->em->find(DocSubsidiary::class, $this->subsidiaryId);
        $mainCategory = $this->em->find(DocMainCategory::class, $this->mainCategoryId);
        $docType      = $this->em->find(DocType::class, $this->docTypeId);
        $subCategory  = $this->em->find(DocSubCategory::class, $this->subCategoryId);

        if (!$subsidiary || !$mainCategory || !$docType || !$subCategory) return '—';

        $docNum = $this->resolvedDocNumber();

        return \sprintf(
            '%s%s-%s-%s-%s-%s-%s',
            $subsidiary->getCode(),
            $mainCategory->getCode(),
            $mainCategory->getReferenceCode(),
            $docType->getCode(),
            $subCategory->getFormattedCode(),
            \sprintf('%03d', $docNum),
            '00'
        );
    }

    private function resolvedDocNumber(): int
    {
        if ($this->docNumberMode === 1) {
            return max(0, (int) $this->manualDocNumber);
        }
        if ($this->predefinedNumberId) {
            $pre = $this->em->find(DocPredefinedNumber::class, $this->predefinedNumberId);
            if ($pre) return $pre->getCode();
        }
        return $this->getSuggestedDocNumber();
    }

    #[LiveAction]
    public function resetConfirmation(): void
    {
        $this->savedDocNumber = '';
    }

    #[LiveAction]
    public function save(): void
    {
        if (!$this->subsidiaryId || !$this->mainCategoryId || !$this->docTypeId
            || !$this->subCategoryId || !$this->title) {
            return;
        }

        $subsidiary   = $this->em->find(DocSubsidiary::class, $this->subsidiaryId);
        $mainCategory = $this->em->find(DocMainCategory::class, $this->mainCategoryId);
        $docType      = $this->em->find(DocType::class, $this->docTypeId);
        $subCategory  = $this->em->find(DocSubCategory::class, $this->subCategoryId);

        if (!$subsidiary || !$mainCategory || !$docType || !$subCategory) return;

        $entry = new DocumentEntry();
        $entry->setSubsidiary($subsidiary);
        $entry->setMainCategory($mainCategory);
        $entry->setReferenceCode($mainCategory->getReferenceCode());
        $entry->setDocType($docType);
        $entry->setSubCategory($subCategory);
        $entry->setDocNumber($this->resolvedDocNumber());
        $entry->setRevision('00');
        $entry->setTitle($this->title);
        $entry->setCreatedBy($this->security->getUser());

        $this->em->persist($entry);
        $this->em->flush();

        $this->savedDocNumber = $entry->getDocumentNumber();

        // Reset form
        $this->subsidiaryId = 0;
        $this->mainCategoryId = 0;
        $this->docTypeId = 0;
        $this->subCategoryId = 0;
        $this->docNumberMode = 0;
        $this->predefinedNumberId = 0;
        $this->manualDocNumber = '';
        $this->title = '';

        $this->emit('entryCreated');
    }
}
