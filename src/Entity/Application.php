<?php

namespace App\Entity;

use App\Repository\ApplicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\Table(name: 'applications')]
#[ORM\UniqueConstraint(name: 'unique_user_offer', columns: ['user_id', 'offer_id'])]
class Application
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Offer::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(name: 'offer_id', nullable: false, onDelete: 'CASCADE')]
    private ?Offer $offer = null;

    #[ORM\Column(name: 'cv_file_path', length: 500, nullable: true)]
    private ?string $cvFilePath = null;

    #[ORM\Column(name: 'motivation_letter', type: Types::TEXT, nullable: true)]
    private ?string $motivationLetter = null;

    #[ORM\Column(length: 50, options: ['default' => 'Nouvelle'])]
    private string $status = 'Nouvelle';

    #[ORM\Column(name: 'application_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $applicationDate = null;

    #[ORM\Column(options: ['default' => 0])]
    private float $score = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $interviewer = null;

    #[ORM\Column(name: 'interview_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $interviewDate = null;

    #[ORM\Column(name: 'interview_result', length: 100, nullable: true)]
    private ?string $interviewResult = null;

    #[ORM\Column(name: 'recruiter_response', type: Types::TEXT, nullable: true)]
    private ?string $recruiterResponse = null;

    #[ORM\Column(name: 'response_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $responseDate = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $workflow = null;

    public function __construct()
    {
        $this->applicationDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }
    public function setOffer(?Offer $offer): static
    {
        $this->offer = $offer;
        return $this;
    }

    public function getCvFilePath(): ?string
    {
        return $this->cvFilePath;
    }
    public function setCvFilePath(?string $cvFilePath): static
    {
        $this->cvFilePath = $cvFilePath;
        return $this;
    }

    public function getMotivationLetter(): ?string
    {
        return $this->motivationLetter;
    }
    public function setMotivationLetter(?string $motivationLetter): static
    {
        $this->motivationLetter = $motivationLetter;
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

    public function getApplicationDate(): ?\DateTimeInterface
    {
        return $this->applicationDate;
    }
    public function setApplicationDate(?\DateTimeInterface $applicationDate): static
    {
        $this->applicationDate = $applicationDate;
        return $this;
    }

    public function getScore(): float
    {
        return $this->score;
    }
    public function setScore(float $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }
    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getInterviewer(): ?string
    {
        return $this->interviewer;
    }
    public function setInterviewer(?string $interviewer): static
    {
        $this->interviewer = $interviewer;
        return $this;
    }

    public function getInterviewDate(): ?\DateTimeInterface
    {
        return $this->interviewDate;
    }
    public function setInterviewDate(?\DateTimeInterface $interviewDate): static
    {
        $this->interviewDate = $interviewDate;
        return $this;
    }

    public function getInterviewResult(): ?string
    {
        return $this->interviewResult;
    }
    public function setInterviewResult(?string $interviewResult): static
    {
        $this->interviewResult = $interviewResult;
        return $this;
    }

    public function getRecruiterResponse(): ?string
    {
        return $this->recruiterResponse;
    }
    public function setRecruiterResponse(?string $recruiterResponse): static
    {
        $this->recruiterResponse = $recruiterResponse;
        return $this;
    }

    public function getResponseDate(): ?\DateTimeInterface
    {
        return $this->responseDate;
    }
    public function setResponseDate(?\DateTimeInterface $responseDate): static
    {
        $this->responseDate = $responseDate;
        return $this;
    }

    public function getWorkflow(): ?array
    {
        return $this->workflow;
    }
    public function setWorkflow(?array $workflow): static
    {
        $this->workflow = $workflow;
        return $this;
    }
}
