<?php

namespace App\Entity;

use App\Repository\DocTitleWordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocTitleWordRepository::class)]
#[ORM\Table(name: 'doc_title_word')]
#[ORM\UniqueConstraint(name: 'doc_title_word_unique_word', columns: ['word'])]
class DocTitleWord
{
    public const TYPE_MINOR = 'minor';
    public const TYPE_UPPERCASE = 'uppercase';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Lowercase word, e.g. "the" or "pid" */
    #[ORM\Column(length: 30)]
    private string $word;

    /** self::TYPE_MINOR or self::TYPE_UPPERCASE */
    #[ORM\Column(length: 20)]
    private string $type;

    public function getId(): ?int { return $this->id; }
    public function getWord(): string { return $this->word; }
    public function setWord(string $word): static { $this->word = $word; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
}
