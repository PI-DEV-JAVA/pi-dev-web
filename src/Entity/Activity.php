<?php
namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
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

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'activities')]
    #[ORM\JoinColumn(name: 'project_id', nullable: true)]
    private ?Project $project = null;

    // --- New Fields for Employee-Admin Interaction ---
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $employeeReport = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminResponse = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'PENDING'])]
    private string $status = 'PENDING'; // PENDING, APPROVED, REJECTED

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deadline = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $submittedAt = null;

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

    public function getEmployeeReport(): ?string
    {
        return $this->employeeReport;
    }

    public function setEmployeeReport(?string $employeeReport): static
    {
        $this->employeeReport = $employeeReport;
        return $this;
    }

    public function getAdminResponse(): ?string
    {
        return $this->adminResponse;
    }

    public function setAdminResponse(?string $adminResponse): static
    {
        $this->adminResponse = $adminResponse;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getDeadline(): ?\DateTimeInterface
    {
        return $this->deadline;
    }

    public function setDeadline(?\DateTimeInterface $deadline): static
    {
        $this->deadline = $deadline;
        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeInterface
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?\DateTimeInterface $submittedAt): static
    {
        $this->submittedAt = $submittedAt;
        return $this;
    }

    // --- Advanced Methods ---

    public function isWeekendActivity(): bool
    {
        if (!$this->activityDate) return false;
        $dayOfWeek = (int)$this->activityDate->format('N');
        return $dayOfWeek === 6 || $dayOfWeek === 7; // 6 = Saturday, 7 = Sunday
    }

    public function getRoundedHours(): float
    {
        if (!$this->hoursWorked) return 0.0;
        $val = (float)$this->hoursWorked;
        // Round to nearest 0.5 (e.g., 2.3 -> 2.5, 2.1 -> 2.0)
        return round($val * 2) / 2;
    }

    public function isOnTime(): bool
    {
        // If there's no deadline, we consider it arbitrarily on time
        if (!$this->deadline) {
            return true;
        }

        // If it isn't submitted yet and today > deadline, it's late.
        $now = new \DateTime();
        
        if (!$this->submittedAt) {
            // Strip hours from strictly date deadline
            $today = new \DateTime($now->format('Y-m-d'));
            $end = new \DateTime($this->deadline->format('Y-m-d'));
            return $today <= $end;
        }

        // It is submitted. Was it submitted before the deadline?
        $sub = new \DateTime($this->submittedAt->format('Y-m-d'));
        $end = new \DateTime($this->deadline->format('Y-m-d'));
        return $sub <= $end;
    }
}
