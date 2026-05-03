<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'activities')]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_activity')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'employee_id', nullable: false)]
    private ?User $employee = null;

    #[ORM\Column(name: 'activity_date', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $activityDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'hours_worked', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $hoursWorked = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(name: 'project_id', nullable: true)]
    private ?Project $project = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userReport = null;

    #[ORM\Column(length: 20, options: ['default' => 'PENDING'])]
    private ?string $reportStatus = 'PENDING';

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getEmployee(): ?User
    {
        return $this->employee;
    }
    public function setEmployee(?User $e): static
    {
        $this->employee = $e;
        return $this;
    }
    public function getActivityDate(): ?\DateTimeInterface
    {
        return $this->activityDate;
    }
    public function setActivityDate(\DateTimeInterface $d): static
    {
        $this->activityDate = $d;
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
    public function getHoursWorked(): ?string
    {
        return $this->hoursWorked;
    }
    public function setHoursWorked(?string $h): static
    {
        $this->hoursWorked = $h;
        return $this;
    }
    public function getProject(): ?Project
    {
        return $this->project;
    }
    public function setProject(?Project $p): static
    {
        $this->project = $p;
        return $this;
    }
    public function getUserReport(): ?string
    {
        return $this->userReport;
    }
    public function setUserReport(?string $r): static
    {
        $this->userReport = $r;
        return $this;
    }
    public function getReportStatus(): ?string
    {
        return $this->reportStatus;
    }
    public function setReportStatus(string $s): static
    {
        $this->reportStatus = $s;
        return $this;
    }

    #[ORM\Column(name: 'expected_deadline', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expectedDeadline = null;

    #[ORM\Column(name: 'is_late_submission', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isLateSubmission = false;

    #[ORM\Column(name: 'delay_in_hours', type: Types::INTEGER, options: ['default' => 0])]
    private int $delayInHours = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminFeedback = null;

    #[ORM\Column(name: 'revision_count', type: Types::INTEGER, options: ['default' => 0])]
    private int $revisionCount = 0;

    public function getExpectedDeadline(): ?\DateTimeInterface { return $this->expectedDeadline; }
    public function setExpectedDeadline(?\DateTimeInterface $d): static { $this->expectedDeadline = $d; return $this; }

    public function isLateSubmission(): bool { return $this->isLateSubmission; }
    public function setIsLateSubmission(bool $l): static { $this->isLateSubmission = $l; return $this; }

    public function getDelayInHours(): int { return $this->delayInHours; }
    public function setDelayInHours(int $d): static { $this->delayInHours = $d; return $this; }

    public function getAdminFeedback(): ?string { return $this->adminFeedback; }
    public function setAdminFeedback(?string $f): static { $this->adminFeedback = $f; return $this; }

    public function getRevisionCount(): int { return $this->revisionCount; }
    public function setRevisionCount(int $c): static { $this->revisionCount = $c; return $this; }
}
