<?php

namespace App\Entity;

use App\Repository\OfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OfferRepository::class)]
#[ORM\Table(name: 'offers')]
class Offer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(name: 'contract_type', length: 50, nullable: true)]
    private ?string $contractType = null;

    #[ORM\Column(name: 'experience_level', length: 50, nullable: true)]
    private ?string $experienceLevel = null;

    #[ORM\Column(name: 'salary_min', nullable: true)]
    private ?float $salaryMin = null;

    #[ORM\Column(name: 'salary_max', nullable: true)]
    private ?float $salaryMax = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(name: 'publish_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publishDate = null;

    #[ORM\Column(name: 'closing_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $closingDate = null;

    #[ORM\Column(name: 'positions_available', options: ['default' => 1])]
    private int $positionsAvailable = 1;

    #[ORM\Column(name: 'applications_received', options: ['default' => 0])]
    private int $applicationsReceived = 0;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'recruiter_id', nullable: true)]
    private ?User $recruiter = null;

    #[ORM\OneToMany(mappedBy: 'offer', targetEntity: Application::class)]
    private Collection $applications;

    public function __construct()
    {
        $this->applications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }
    public function setDepartment(?string $department): static
    {
        $this->department = $department;
        return $this;
    }

    public function getContractType(): ?string
    {
        return $this->contractType;
    }
    public function setContractType(?string $contractType): static
    {
        $this->contractType = $contractType;
        return $this;
    }

    public function getExperienceLevel(): ?string
    {
        return $this->experienceLevel;
    }
    public function setExperienceLevel(?string $experienceLevel): static
    {
        $this->experienceLevel = $experienceLevel;
        return $this;
    }

    public function getSalaryMin(): ?float
    {
        return $this->salaryMin;
    }
    public function setSalaryMin(?float $salaryMin): static
    {
        $this->salaryMin = $salaryMin;
        return $this;
    }

    public function getSalaryMax(): ?float
    {
        return $this->salaryMax;
    }
    public function setSalaryMax(?float $salaryMax): static
    {
        $this->salaryMax = $salaryMax;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }
    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }
    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPublishDate(): ?\DateTimeInterface
    {
        return $this->publishDate;
    }
    public function setPublishDate(?\DateTimeInterface $publishDate): static
    {
        $this->publishDate = $publishDate;
        return $this;
    }

    public function getClosingDate(): ?\DateTimeInterface
    {
        return $this->closingDate;
    }
    public function setClosingDate(?\DateTimeInterface $closingDate): static
    {
        $this->closingDate = $closingDate;
        return $this;
    }

    public function getPositionsAvailable(): int
    {
        return $this->positionsAvailable;
    }
    public function setPositionsAvailable(int $positionsAvailable): static
    {
        $this->positionsAvailable = $positionsAvailable;
        return $this;
    }

    public function getApplicationsReceived(): int
    {
        return $this->applicationsReceived;
    }
    public function setApplicationsReceived(int $applicationsReceived): static
    {
        $this->applicationsReceived = $applicationsReceived;
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

    // BACKWARD COMPATIBILITY getter for templates/controllers that haven't been migrated yet
    public function getRecruiterId(): ?int
    {
        return $this->recruiter?->getId();
    }

    /** @return Collection<int, Application> */
    public function getApplications(): Collection
    {
        return $this->applications;
    }
}
