<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'seance')]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'seances')]
    #[ORM\JoinColumn(name: 'formation_id', nullable: false)]
    private ?Formation $formation = null;

    #[ORM\Column(length: 150)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', columnDefinition: "ENUM('PRESENTIEL','EN_LIGNE')")]
    private ?string $type = null;

    #[ORM\Column(name: 'date_debut', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'date_fin', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(name: 'video_path', length: 255, nullable: true)]
    private ?string $videoPath = null;

    #[ORM\Column(name: 'duree_minutes', nullable: true)]
    private ?int $dureeMinutes = null;

    #[ORM\Column(type: 'string', columnDefinition: "ENUM('PLANIFIEE','EN_COURS','TERMINEE')", nullable: true, options: ['default' => 'PLANIFIEE'])]
    private ?string $statut = 'PLANIFIEE';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToOne(mappedBy: 'seance', targetEntity: Quiz::class, cascade: ['remove'])]
    private ?Quiz $quiz = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }

    public function getType(): ?string
    {
        return $this->type;
    }
    public function setType(string $t): static
    {
        $this->type = $t;
        return $this;
    }
    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }
    public function setDateDebut(\DateTimeInterface $d): static
    {
        $this->dateDebut = $d;
        return $this;
    }
    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }
    public function setDateFin(\DateTimeInterface $d): static
    {
        $this->dateFin = $d;
        return $this;
    }
    public function getAdresse(): ?string
    {
        return $this->adresse;
    }
    public function setAdresse(?string $a): static
    {
        $this->adresse = $a;
        return $this;
    }
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }
    public function setLatitude(?float $l): static
    {
        $this->latitude = $l;
        return $this;
    }
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }
    public function setLongitude(?float $l): static
    {
        $this->longitude = $l;
        return $this;
    }
    public function getVideoPath(): ?string
    {
        return $this->videoPath;
    }
    public function setVideoPath(?string $v): static
    {
        $this->videoPath = $v;
        return $this;
    }
    public function getDureeMinutes(): ?int
    {
        return $this->dureeMinutes;
    }
    public function setDureeMinutes(?int $d): static
    {
        $this->dureeMinutes = $d;
        return $this;
    }
    public function getStatut(): ?string
    {
        return $this->statut;
    }
    public function setStatut(?string $s): static
    {
        $this->statut = $s;
        return $this;
    }
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getQuiz(): ?Quiz { return $this->quiz; }
    public function setQuiz(?Quiz $q): static { $this->quiz = $q; return $this; }

    /** Returns true if seance has ended */
    public function isTerminee(): bool
    {
        return $this->dateFin !== null && $this->dateFin < new \DateTime();
    }

    /** Returns true if quiz window is open (seance ended AND within 24h after) */
    public function isQuizUnlocked(): bool
    {
        if (!$this->isTerminee()) return false;
        $deadline = clone $this->dateFin;
        $deadline->modify('+24 hours');
        return new \DateTime() <= $deadline;
    }
}
