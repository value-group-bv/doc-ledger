<?php

namespace App\Entity;

use App\Repository\DocMainCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocMainCategoryRepository::class)]
#[ORM\Table(name: 'doc_main_category')]
class DocMainCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Single digit 0–9 */
    #[ORM\Column(length: 1, unique: true)]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $description;

    /** Reference code format used in document numbers: '000' (numeric) or 'AAA' (alphabetic) */
    #[ORM\Column(length: 3, options: ['default' => '000'])]
    private string $referenceCode = '000';

    #[ORM\OneToMany(targetEntity: DocumentEntry::class, mappedBy: 'mainCategory')]
    private Collection $documentEntries;

    public function __construct()
    {
        $this->documentEntries = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getReferenceCode(): string { return $this->referenceCode; }
    public function setReferenceCode(string $referenceCode): static { $this->referenceCode = $referenceCode; return $this; }

    public function __toString(): string { return "{$this->code} — {$this->description}"; }
}
