<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\Table(name: 'activities')]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_activity')]
    private ?int $id = null;

    #[ORM\Column(name: 'activity_date', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "Date is required.")]
    private ?\DateTime $activityDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "Description is required.")]
    #[Assert\Length(min: 10, message: "Describe your work in at least 10 characters.")]
    private ?string $description = null;

    #[ORM\Column(name: 'hours_worked', type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank(message: "Hours are required.")]
    #[Assert\Range(min: 0.1, max: 24, notInRangeMessage: "Hours must be between 0.1 and 24.")]
    private ?string $hoursWorked = null;

    #[ORM\Column(name: 'last_activity_time', nullable: true)]
    private ?\DateTimeImmutable $lastActivityTime = null;

    #[ORM\Column(name: 'total_tracked_seconds', type: Types::BIGINT)]
    private ?string $totalTrackedSeconds = "0";

    #[ORM\Column(name: 'is_tracking')]
    private ?bool $isTracking = false;

    #[ORM\ManyToOne(inversedBy: 'activities')]
    #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: "Please select a project.")]
    private ?Project $Project = null;

    #[ORM\ManyToOne(inversedBy: 'activities')]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: "Please assign an employee.")]
    private ?User $employee = null;

    public function getId(): ?int { return $this->id; }
    public function getActivityDate(): ?\DateTime { return $this->activityDate; }
    public function setActivityDate(\DateTime $activityDate): static { $this->activityDate = $activityDate; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getHoursWorked(): ?string { return $this->hoursWorked; }
    public function setHoursWorked(string $hoursWorked): static { $this->hoursWorked = $hoursWorked; return $this; }
    public function getLastActivityTime(): ?\DateTimeImmutable { return $this->lastActivityTime; }
    public function setLastActivityTime(?\DateTimeImmutable $lastActivityTime): static { $this->lastActivityTime = $lastActivityTime; return $this; }
    public function getTotalTrackedSeconds(): ?string { return $this->totalTrackedSeconds; }
    public function setTotalTrackedSeconds(string $totalTrackedSeconds): static { $this->totalTrackedSeconds = $totalTrackedSeconds; return $this; }
    public function isTracking(): ?bool { return $this->isTracking; }
    public function setIsTracking(bool $isTracking): static { $this->isTracking = $isTracking; return $this; }
    public function getProject(): ?Project { return $this->Project; }
    public function setProject(?Project $Project): static { $this->Project = $Project; return $this; }
    public function getEmployee(): ?User { return $this->employee; }
    public function setEmployee(?User $employee): static { $this->employee = $employee; return $this; }
}