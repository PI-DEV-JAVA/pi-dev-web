<?php
namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
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

    #[ORM\Column(type: 'string', columnDefinition: "ENUM('HIGH','MEDIUM','LOW')", options: ['default' => 'MEDIUM'])]
    private ?string $priority = 'MEDIUM';

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isArchived = false;

    #[ORM\Column(name: 'start_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(name: 'end_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $budget = null;

    #[ORM\Column(name: 'project_manager_id', nullable: true)]
    private ?int $projectManagerId = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $timeSpent = 0;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: Activity::class, cascade: ['persist', 'remove'])]
    private Collection $activities;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->activities = new ArrayCollection();
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

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): static
    {
        $this->isArchived = $isArchived;
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

    public function getProjectManagerId(): ?int
    {
        return $this->projectManagerId;
    }

    public function setProjectManagerId(?int $id): static
    {
        $this->projectManagerId = $id;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getTimeSpent(): int
    {
        return $this->timeSpent;
    }

    public function setTimeSpent(int $timeSpent): static
    {
        $this->timeSpent = $timeSpent;
        return $this;
    }

    public function getTimeSpentFormatted(): string
    {
        $s = $this->timeSpent;
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        if ($h > 0) {
            return $h . 'h ' . str_pad($m, 2, '0', STR_PAD_LEFT) . 'min';
        }
        return $m . 'min';
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): static
    {
        if (!$this->activities->contains($activity)) {
            $this->activities->add($activity);
            $activity->setProject($this);
        }
        return $this;
    }

    public function removeActivity(Activity $activity): static
    {
        if ($this->activities->removeElement($activity)) {
            if ($activity->getProject() === $this) {
                $activity->setProject(null);
            }
        }
        return $this;
    }

    // --- Advanced Methods ---

    public function getDurationInDays(): ?int
    {
        if ($this->startDate && $this->endDate) {
            $diff = $this->endDate->diff($this->startDate);
            return (int)$diff->format('%a');
        }
        return null;
    }

    public function isOverdue(): bool
    {
        if (!$this->endDate || $this->status === 'DONE') {
            return false;
        }
        $today = new \DateTime();
        $today->setTime(0,0,0);
        return $this->endDate < $today;
    }

    public function getTotalHoursLogged(): float
    {
        $total = 0.0;
        foreach ($this->activities as $activity) {
            if ($activity->getHoursWorked()) {
                $total += (float)$activity->getHoursWorked();
            }
        }
        return $total;
    }

    public function getProgressPercentage(): int
    {
        if ($this->status === 'DONE') return 100;
        if ($this->status === 'PLANNED') return 0;
        
        $duration = $this->getDurationInDays();
        if (!$duration || !$this->startDate) {
            // estimate based on hours/some arbitrary value if no dates
            return $this->status === 'IN_PROGRESS' ? 50 : 0;
        }

        $today = new \DateTime();
        if ($this->startDate > $today) return 0;
        if ($this->endDate && $this->endDate < $today) return 99; // Almost done or overdue

        $elapsed = $today->diff($this->startDate)->format('%a');
        $percentage = (int)(($elapsed / $duration) * 100);
        return min($percentage, 99); // Max 99 if not explicitly DONE
    }
}
