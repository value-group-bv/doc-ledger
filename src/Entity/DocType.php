<?php

namespace App\Entity;

use App\Repository\DocTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocTypeRepository::class)]
#[ORM\Table(name: 'doc_type')]
class DocType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** E.g. DWG, APP, PLA, REP */
    #[ORM\Column(length: 10, unique: true)]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $description;

    #[ORM\Column(options: ['default' => 0])]
    private int $sortOrder = 0;

    #[ORM\OneToMany(targetEntity: DocSubCategory::class, mappedBy: 'docType', cascade: ['persist'])]
    #[ORM\OrderBy(['code' => 'ASC'])]
    private Collection $subCategories;

    #[ORM\OneToMany(targetEntity: DocumentEntry::class, mappedBy: 'docType')]
    private Collection $documentEntries;

    public function __construct()
    {
        $this->subCategories = new ArrayCollection();
        $this->documentEntries = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): static { $this->sortOrder = $sortOrder; return $this; }

    /** @return Collection<int, DocSubCategory> */
    public function getSubCategories(): Collection { return $this->subCategories; }

    public function __toString(): string { return "{$this->code} — {$this->description}"; }
}
