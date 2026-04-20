<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'choix')]
class Choix
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'choix')]
    #[ORM\JoinColumn(name: 'question_id', nullable: false)]
    private ?Question $question = null;

    #[ORM\Column(length: 500)]
    private ?string $texte = null;

    #[ORM\Column(name: 'is_correct', type: 'boolean', options: ['default' => false])]
    private bool $isCorrect = false;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getQuestion(): ?Question
    {
        return $this->question;
    }
    public function setQuestion(?Question $q): static
    {
        $this->question = $q;
        return $this;
    }
    public function getTexte(): ?string
    {
        return $this->texte;
    }
    public function setTexte(string $t): static
    {
        $this->texte = $t;
        return $this;
    }
    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }
    public function setIsCorrect(bool $c): static
    {
        $this->isCorrect = $c;
        return $this;
    }
}
