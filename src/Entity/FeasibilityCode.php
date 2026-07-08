<?php

namespace App\Entity;

use App\Repository\FeasibilityCodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeasibilityCodeRepository::class)]
#[ORM\Table(name: 'feasibility_code')]
class FeasibilityCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 3, unique: true, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255)]
    private string $requestor;

    #[ORM\ManyToOne(targetEntity: DocSubsidiary::class)]
    #[ORM\JoinColumn(nullable: false)]
    private DocSubsidiary $subsidiary;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCode(): ?string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getRequestor(): string { return $this->requestor; }
    public function setRequestor(string $requestor): static { $this->requestor = $requestor; return $this; }

    public function getSubsidiary(): DocSubsidiary { return $this->subsidiary; }
    public function setSubsidiary(DocSubsidiary $subsidiary): static { $this->subsidiary = $subsidiary; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
