<?php

namespace App\Entity;

use App\Repository\DocSubCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocSubCategoryRepository::class)]
#[ORM\Table(name: 'doc_sub_category')]
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

    /** At least one; a code may be shared by several doc types without repeating it per type */
    #[ORM\ManyToMany(targetEntity: DocType::class, inversedBy: 'subCategories')]
    #[ORM\JoinTable(name: 'doc_sub_category_doc_type')]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'code' => 'ASC'])]
    private Collection $docTypes;

    /** Null means this sub category applies to all main categories */
    #[ORM\ManyToOne(targetEntity: DocMainCategory::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?DocMainCategory $mainCategory = null;

    /** Null means this sub category applies to all subsidiaries */
    #[ORM\ManyToOne(targetEntity: DocSubsidiary::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?DocSubsidiary $subsidiary = null;

    #[ORM\OneToMany(targetEntity: DocumentEntry::class, mappedBy: 'subCategory')]
    private Collection $documentEntries;

    public function __construct()
    {
        $this->docTypes = new ArrayCollection();
        $this->documentEntries = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getCode(): int { return $this->code; }
    public function setCode(int $code): static { $this->code = $code; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    /** @return Collection<int, DocType> */
    public function getDocTypes(): Collection { return $this->docTypes; }

    public function hasDocType(DocType $docType): bool { return $this->docTypes->contains($docType); }

    public function addDocType(DocType $docType): static
    {
        if (!$this->docTypes->contains($docType)) {
            $this->docTypes->add($docType);
        }
        return $this;
    }

    public function removeDocType(DocType $docType): static
    {
        $this->docTypes->removeElement($docType);
        return $this;
    }

    /** Replaces the doc types, only touching the ones that actually change. @param DocType[] $docTypes */
    public function setDocTypes(array $docTypes): static
    {
        foreach ($this->docTypes->toArray() as $docType) {
            if (!\in_array($docType, $docTypes, true)) {
                $this->docTypes->removeElement($docType);
            }
        }
        foreach ($docTypes as $docType) {
            $this->addDocType($docType);
        }
        return $this;
    }

    public function getMainCategory(): ?DocMainCategory { return $this->mainCategory; }
    public function setMainCategory(?DocMainCategory $mainCategory): static { $this->mainCategory = $mainCategory; return $this; }

    public function getSubsidiary(): ?DocSubsidiary { return $this->subsidiary; }
    public function setSubsidiary(?DocSubsidiary $subsidiary): static { $this->subsidiary = $subsidiary; return $this; }

    public function getFormattedCode(): string { return \sprintf('%03d', $this->code); }

    public function __toString(): string { return "{$this->getFormattedCode()} — {$this->description}"; }
}
