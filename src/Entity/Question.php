<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'question')]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'quiz_id', nullable: false)]
    private ?Quiz $quiz = null;

    #[ORM\Column(length: 500)]
    private ?string $enonce = null;

    #[ORM\OneToMany(mappedBy: 'question', targetEntity: Choix::class, cascade: ['persist', 'remove'])]
    private Collection $choix;

    public function __construct()
    {
        $this->choix = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }
    public function setQuiz(?Quiz $q): static
    {
        $this->quiz = $q;
        return $this;
    }
    public function getEnonce(): ?string
    {
        return $this->enonce;
    }
    public function setEnonce(string $e): static
    {
        $this->enonce = $e;
        return $this;
    }
    /** @return Collection<int, Choix> */
    public function getChoix(): Collection
    {
        return $this->choix;
    }
}
