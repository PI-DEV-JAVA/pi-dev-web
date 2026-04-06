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
}
