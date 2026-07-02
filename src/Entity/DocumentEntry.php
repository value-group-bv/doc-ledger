<?php

namespace App\Entity;

use App\Repository\DocumentEntryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DocumentEntryRepository::class)]
#[ORM\Table(name: 'document_entry')]
#[ORM\UniqueConstraint(name: 'document_entry_unique_id', columns: ['subsidiary_id', 'main_category_id', 'doc_type_id', 'sub_category_id', 'doc_number', 'revision'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['subsidiary', 'mainCategory', 'docType', 'subCategory', 'docNumber', 'revision'], message: 'This document ID already exists in the ledger.')]
class DocumentEntry
{
    /* Words kept lowercase in Title Case unless first or last */
    private const TITLE_CASE_MINOR_WORDS = [
        'a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'in',
        'nor', 'of', 'on', 'or', 'so', 'the', 'to', 'up', 'yet',
    ];

    /* Abbreviations always fully capitalised, e.g. "pid" → "PID" */
    private const UPPERCASE_WORDS = [
        'pid', 'pfd', 'iso', 'api', 'iec', 'ansi', 'astm', 'bs', 'din', 'en'
    ];

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
    public function setMainCategory(DocMainCategory $mainCategory): static { $this->mainCategory = $mainCategory; return $this; }

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

    public function setTitle(string $title): static
    {
        $title = trim(preg_replace('/[^A-Za-z0-9 ]/', '', $title));
        $title = preg_replace('/\s+/', ' ', $title);

        $words = explode(' ', strtolower($title));
        $lastIndex = count($words) - 1;
        foreach ($words as $i => &$word) {
            if ($word === '') continue;

            if (in_array($word, self::UPPERCASE_WORDS, true)) {
                $word = strtoupper($word);
            } elseif ($i === 0 || $i === $lastIndex || !in_array($word, self::TITLE_CASE_MINOR_WORDS, true)) {
                $word = ucfirst($word);
            }
        }
        unset($word);

        $this->title = implode(' ', $words);
        return $this;
    }

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
