<?php

namespace App\Entity;

use App\Repository\DocSubsidiaryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DocSubsidiaryRepository::class)]
#[ORM\Table(name: 'doc_subsidiary')]
class DocSubsidiary
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 2, unique: true)]
    #[Assert\Regex(
        pattern: '/^[A-Za-z]{2}$/',
        message: 'Subsidiary code must be exactly 2 letters (no numbers or special characters)'
    )]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $description;

    #[ORM\Column(options: ['default' => 0])]
    private int $sortOrder = 0;

    #[ORM\OneToMany(targetEntity: DocumentEntry::class, mappedBy: 'subsidiary')]
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
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): static { $this->sortOrder = $sortOrder; return $this; }
    public function getDocumentEntries(): Collection { return $this->documentEntries; }

    public function __toString(): string { return "{$this->code} — {$this->description}"; }
}
