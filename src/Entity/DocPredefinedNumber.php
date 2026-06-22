<?php

namespace App\Entity;

use App\Repository\DocPredefinedNumberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocPredefinedNumberRepository::class)]
#[ORM\Table(name: 'doc_predefined_number')]
#[ORM\UniqueConstraint(fields: ['code', 'subCategory'])]
class DocPredefinedNumber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** 3-digit number, e.g. 002 */
    #[ORM\Column]
    private int $code;

    #[ORM\Column(length: 200)]
    private string $description;

    #[ORM\ManyToOne(targetEntity: DocSubCategory::class, inversedBy: 'predefinedNumbers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private DocSubCategory $subCategory;

    public function getId(): ?int { return $this->id; }
    public function getCode(): int { return $this->code; }
    public function setCode(int $code): static { $this->code = $code; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function getSubCategory(): DocSubCategory { return $this->subCategory; }
    public function setSubCategory(DocSubCategory $subCategory): static { $this->subCategory = $subCategory; return $this; }

    public function getFormattedCode(): string { return sprintf('%03d', $this->code); }

    public function __toString(): string { return "{$this->getFormattedCode()} — {$this->description}"; }
}
