<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quiz')]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'quizzes')]
    #[ORM\JoinColumn(name: 'formation_id', nullable: false)]
    private ?Formation $formation = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(nullable: true)]
    private ?int $duree = null;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: Question::class, cascade: ['persist', 'remove'])]
    private Collection $questions;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getFormation(): ?Formation
    {
        return $this->formation;
    }
    public function setFormation(?Formation $f): static
    {
        $this->formation = $f;
        return $this;
    }
    public function getTitre(): ?string
    {
        return $this->titre;
    }
    public function setTitre(string $t): static
    {
        $this->titre = $t;
        return $this;
    }
    public function getDuree(): ?int
    {
        return $this->duree;
    }
    public function setDuree(?int $d): static
    {
        $this->duree = $d;
        return $this;
    }
    /** @return Collection<int, Question> */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }
}
