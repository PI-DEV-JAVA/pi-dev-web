<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'formation')]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $niveau = null;

    #[ORM\Column(nullable: true)]
    private ?int $duree = null;

    #[ORM\Column(name: 'date_debut', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'date_fin', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'recruiter_id', nullable: true)]
    private ?User $recruiter = null;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Seance::class, cascade: ['remove'])]
    private Collection $seances;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Quiz::class, cascade: ['remove'])]
    private Collection $quizzes;

    public function __construct()
    {
        $this->seances = new ArrayCollection();
        $this->quizzes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $d): static
    {
        $this->description = $d;
        return $this;
    }
    public function getNiveau(): ?string
    {
        return $this->niveau;
    }
    public function setNiveau(?string $n): static
    {
        $this->niveau = $n;
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
    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }
    public function setDateDebut(?\DateTimeInterface $d): static
    {
        $this->dateDebut = $d;
        return $this;
    }
    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }
    public function setDateFin(?\DateTimeInterface $d): static
    {
        $this->dateFin = $d;
        return $this;
    }
    public function getRecruiter(): ?User
    {
        return $this->recruiter;
    }
    public function setRecruiter(?User $user): static
    {
        $this->recruiter = $user;
        return $this;
    }

    public function getRecruiterId(): ?int
    {
        return $this->recruiter?->getId();
    }
    /** @return Collection<int, Seance> */
    public function getSeances(): Collection
    {
        return $this->seances;
    }
    /** @return Collection<int, Quiz> */
    public function getQuizzes(): Collection
    {
        return $this->quizzes;
    }
}
