<?php

namespace App\Entity;

use App\Repository\DocumentEntryRepository;
use App\Validator\UniqueDocumentId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DocumentEntryRepository::class)]
#[ORM\Table(name: 'document_entry')]
#[ORM\UniqueConstraint(name: 'document_entry_unique_id', columns: ['subsidiary_id', 'main_category_id', 'doc_type_id', 'sub_category_id', 'doc_number', 'revision'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueDocumentId]
class DocumentEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: DocSubsidiary::class, inversedBy: 'documentEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private DocSubsidiary $subsidiary;

    #[ORM\ManyToOne(targetEntity: DocMainCategory::class, inversedBy: 'documentEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private DocMainCategory $mainCategory;

    /**
     * Extra main categories this document is also valid under, e.g. a feasibility (1) drawing
     * that carries over to installation (5) once a project is signed. The ledger can display
     * the document under any of them; the stored mainCategory remains the default.
     */
    #[ORM\ManyToMany(targetEntity: DocMainCategory::class)]
    #[ORM\JoinTable(name: 'document_entry_alt_main_category')]
    #[ORM\OrderBy(['code' => 'ASC'])]
    private Collection $alternateMainCategories;

    /**
     * Project/reference code — numeric (001, 002…) for signed projects,
     * alphabetic (AAA, AAB…) for feasibility, or fixed abbreviation for product lines.
     */
    #[ORM\Column(length: 20)]
    private string $referenceCode;

    #[ORM\ManyToOne(targetEntity: DocType::class, inversedBy: 'documentEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private DocType $docType;

    #[ORM\ManyToOne(targetEntity: DocSubCategory::class, inversedBy: 'documentEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private DocSubCategory $subCategory;

    /** 0–999 */
    #[ORM\Column(options: ['default' => 0])]
    private int $docNumber = 0;

    /**
     * Final releases: 00, 01, 02…
     * Interim versions: 0A, 0B, 1F…
     */
    #[ORM\Column(length: 10, options: ['default' => '00'])]
    private string $revision = '00';

    #[ORM\Column(length: 48)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comments = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->alternateMainCategories = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid { return $this->id; }

    public function getSubsidiary(): DocSubsidiary { return $this->subsidiary; }
    public function setSubsidiary(DocSubsidiary $subsidiary): static { $this->subsidiary = $subsidiary; return $this; }

    public function getMainCategory(): DocMainCategory { return $this->mainCategory; }
    public function setMainCategory(DocMainCategory $mainCategory): static
    {
        $this->mainCategory = $mainCategory;
        $this->alternateMainCategories->removeElement($mainCategory);
        return $this;
    }

    /** @return Collection<int, DocMainCategory> */
    public function getAlternateMainCategories(): Collection { return $this->alternateMainCategories; }

    public function addAlternateMainCategory(DocMainCategory $mainCategory): static
    {
        // The default is implicitly allowed; storing it again would only duplicate it.
        if ($mainCategory !== ($this->mainCategory ?? null) && !$this->alternateMainCategories->contains($mainCategory)) {
            $this->alternateMainCategories->add($mainCategory);
        }
        return $this;
    }

    public function removeAlternateMainCategory(DocMainCategory $mainCategory): static
    {
        $this->alternateMainCategories->removeElement($mainCategory);
        return $this;
    }

    /** @return DocMainCategory[] The default main category first, followed by the alternates */
    public function getAllowedMainCategories(): array
    {
        return [$this->mainCategory, ...$this->alternateMainCategories->toArray()];
    }

    public function getReferenceCode(): string { return $this->referenceCode; }
    public function setReferenceCode(string $referenceCode): static { $this->referenceCode = $referenceCode; return $this; }

    public function getDocType(): DocType { return $this->docType; }
    public function setDocType(DocType $docType): static { $this->docType = $docType; return $this; }

    public function getSubCategory(): DocSubCategory { return $this->subCategory; }
    public function setSubCategory(DocSubCategory $subCategory): static { $this->subCategory = $subCategory; return $this; }

    public function getDocNumber(): int { return $this->docNumber; }
    public function setDocNumber(int $docNumber): static { $this->docNumber = $docNumber; return $this; }

    public function getRevision(): string { return $this->revision; }
    public function setRevision(string $revision): static { $this->revision = $revision; return $this; }

    public function getTitle(): string { return $this->title; }

    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getComments(): ?string { return $this->comments; }
    public function setComments(?string $comments): static { $this->comments = $comments; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    /** Generates the full document ID string, e.g. VM2-001-DWG-300-002-00 */
    public function getDocumentId(): string
    {
        return sprintf(
            '%s%s-%s-%s-%s-%s-%s',
            $this->subsidiary->getCode(),
            $this->mainCategory->getCode(),
            $this->referenceCode,
            $this->docType->getCode(),
            $this->subCategory->getFormattedCode(),
            sprintf('%03d', $this->docNumber),
            $this->revision
        );
    }

    /**
     * Document ID as it reads under the given main category, using that category's reference
     * code placeholder, e.g. VM5-000-DWG-100-110-00 for an entry registered as VM1-AAA-….
     */
    public function getDocumentIdFor(DocMainCategory $mainCategory): string
    {
        if ($mainCategory === $this->mainCategory) {
            return $this->getDocumentId();
        }

        return sprintf(
            '%s%s-%s-%s-%s-%s-%s',
            $this->subsidiary->getCode(),
            $mainCategory->getCode(),
            $mainCategory->getReferenceCode(),
            $this->docType->getCode(),
            $this->subCategory->getFormattedCode(),
            sprintf('%03d', $this->docNumber),
            $this->revision
        );
    }

    /** Document ID without revision suffix, e.g. VM2-001-DWG-300-002 */
    public function getDocumentIdBase(): string
    {
        return sprintf(
            '%s%s-%s-%s-%s-%s',
            $this->subsidiary->getCode(),
            $this->mainCategory->getCode(),
            $this->referenceCode,
            $this->docType->getCode(),
            $this->subCategory->getFormattedCode(),
            sprintf('%03d', $this->docNumber)
        );
    }
}
