<?php

namespace App\Entity;

use App\Repository\DocSubCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocSubCategoryRepository::class)]
#[ORM\Table(name: 'doc_sub_category')]
#[ORM\UniqueConstraint(fields: ['code', 'docType', 'subsidiary'])]
class DocSubCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** 3-digit code, e.g. 100 (Structural), 300 (Electrical) */
    #[ORM\Column]
    private int $code;

    #[ORM\Column(length: 150)]
    private string $description;

    #[ORM\ManyToOne(targetEntity: DocType::class, inversedBy: 'subCategories')]
    #[ORM\JoinColumn(nullable: false)]
    private DocType $docType;

    /** Null means this sub category applies to all subsidiaries */
    #[ORM\ManyToOne(targetEntity: DocSubsidiary::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?DocSubsidiary $subsidiary = null;

    #[ORM\OneToMany(targetEntity: DocPredefinedNumber::class, mappedBy: 'subCategory', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['code' => 'ASC'])]
    private Collection $predefinedNumbers;

    #[ORM\OneToMany(targetEntity: DocumentEntry::class, mappedBy: 'subCategory')]
    private Collection $documentEntries;

    public function __construct()
    {
        $this->predefinedNumbers = new ArrayCollection();
        $this->documentEntries = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getCode(): int { return $this->code; }
    public function setCode(int $code): static { $this->code = $code; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function getDocType(): DocType { return $this->docType; }
    public function setDocType(DocType $docType): static { $this->docType = $docType; return $this; }

    public function getSubsidiary(): ?DocSubsidiary { return $this->subsidiary; }
    public function setSubsidiary(?DocSubsidiary $subsidiary): static { $this->subsidiary = $subsidiary; return $this; }

    /** @return Collection<int, DocPredefinedNumber> */
    public function getPredefinedNumbers(): Collection { return $this->predefinedNumbers; }

    public function getFormattedCode(): string { return \sprintf('%03d', $this->code); }

    public function __toString(): string { return "{$this->getFormattedCode()} — {$this->description}"; }
}
