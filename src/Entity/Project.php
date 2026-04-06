<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'project')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Project name is required.")]
    #[Assert\Length(
        min: 3, 
        minMessage: "Name must be at least {{ limit }} characters."
    )]
    private ?string $name = null; // Property must follow its attributes

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null; // Property must follow its attributes
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Please select a status.")]
    private ?string $status = null;

    #[ORM\Column(name: 'start_date', type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: "Start date is required.")]
    private ?\DateTime $startDate = null;

    #[ORM\Column(name: 'end_date', type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: "End date is required.")]
    #[Assert\GreaterThanOrEqual(propertyPath: "startDate", message: "End date cannot be before start date.")]
    private ?\DateTime $endDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: "Budget must be 0 or more.")]
    private ?string $budget = null;

    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'Project', cascade: ['remove'], orphanRemoval: true)]
    private Collection $activities;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
    public function getStartDate(): ?\DateTime { return $this->startDate; }
    public function setStartDate(?\DateTime $startDate): static { $this->startDate = $startDate; return $this; }
    public function getEndDate(): ?\DateTime { return $this->endDate; }
    public function setEndDate(?\DateTime $endDate): static { $this->endDate = $endDate; return $this; }
    public function getBudget(): ?string { return $this->budget; }
    public function setBudget(?string $budget): static { $this->budget = $budget; return $this; }
    public function getActivities(): Collection { return $this->activities; }

    public function __toString(): string
    {
        return $this->name ?? 'Unnamed Project';
    }
}