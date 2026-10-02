<?php

namespace App\Entity;

use App\Repository\ReservedFeasibilityCodeRepository;
use Doctrine\ORM\Mapping as ORM;

/** An AAA–ZZZ code kept out of the feasibility pool, e.g. because a product (PRO) already uses it as its reference code */
#[ORM\Entity(repositoryClass: ReservedFeasibilityCodeRepository::class)]
#[ORM\Table(name: 'reserved_feasibility_code')]
class ReservedFeasibilityCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 3, unique: true)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $description;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
