<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'project')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', columnDefinition: "ENUM('PLANNED','IN_PROGRESS','DONE','ON_HOLD')", options: ['default' => 'PLANNED'])]
    private ?string $status = 'PLANNED';

    #[ORM\Column(name: 'start_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(name: 'end_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $budget = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'project_manager_id', nullable: true)]
    private ?User $projectManager = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(string $n): static
    {
        $this->name = $n;
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
    public function getStatus(): ?string
    {
        return $this->status;
    }
    public function setStatus(string $s): static
    {
        $this->status = $s;
        return $this;
    }
    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }
    public function setStartDate(?\DateTimeInterface $d): static
    {
        $this->startDate = $d;
        return $this;
    }
    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }
    public function setEndDate(?\DateTimeInterface $d): static
    {
        $this->endDate = $d;
        return $this;
    }
    public function getBudget(): ?string
    {
        return $this->budget;
    }
    public function setBudget(?string $b): static
    {
        $this->budget = $b;
        return $this;
    }
    public function getProjectManager(): ?User
    {
        return $this->projectManager;
    }
    public function setProjectManager(?User $user): static
    {
        $this->projectManager = $user;
        return $this;
    }

    public function getProjectManagerId(): ?int
    {
        return $this->projectManager?->getId();
    }
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
