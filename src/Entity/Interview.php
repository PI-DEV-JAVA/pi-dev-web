<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'interviews')]
class Interview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Application::class)]
    #[ORM\JoinColumn(name: 'application_id', nullable: false)]
    private ?Application $application = null;

    #[ORM\Column(name: 'interview_date', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $interviewDate = null;

    #[ORM\Column(length: 50, options: ['default' => 'SCHEDULED'])]
    private string $status = 'SCHEDULED';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(name: 'meeting_link', length: 500, nullable: true)]
    private ?string $meetingLink = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'interview', targetEntity: Meet::class, cascade: ['persist', 'remove'])]
    private Collection $meets;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->meets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getApplication(): ?Application
    {
        return $this->application;
    }
    public function setApplication(?Application $a): static
    {
        $this->application = $a;
        return $this;
    }
    public function getInterviewDate(): ?\DateTimeInterface
    {
        return $this->interviewDate;
    }
    public function setInterviewDate(\DateTimeInterface $d): static
    {
        $this->interviewDate = $d;
        return $this;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $s): static
    {
        $this->status = $s;
        return $this;
    }
    public function getNotes(): ?string
    {
        return $this->notes;
    }
    public function setNotes(?string $n): static
    {
        $this->notes = $n;
        return $this;
    }
    public function getLocation(): ?string
    {
        return $this->location;
    }
    public function setLocation(?string $l): static
    {
        $this->location = $l;
        return $this;
    }
    public function getMeetingLink(): ?string
    {
        return $this->meetingLink;
    }
    public function setMeetingLink(?string $l): static
    {
        $this->meetingLink = $l;
        return $this;
    }
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, Meet>
     */
    public function getMeets(): Collection
    {
        return $this->meets;
    }
}
