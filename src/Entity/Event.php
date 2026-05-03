<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'event')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'event_type', type: 'string', columnDefinition: "ENUM('MEETUP','CONFERENCE','WORKSHOP','WEBINAR')", options: ['default' => 'MEETUP'])]
    private ?string $eventType = 'MEETUP';

    #[ORM\Column(name: 'event_date', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $eventDate = null;

    #[ORM\Column(name: 'end_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(name: 'is_online', type: 'boolean', options: ['default' => false])]
    private bool $isOnline = false;

    #[ORM\Column(name: 'online_link', length: 500, nullable: true)]
    private ?string $onlineLink = null;

    #[ORM\Column(name: 'max_capacity', options: ['default' => 0])]
    private int $maxCapacity = 0;

    #[ORM\Column(name: 'cover_image', length: 500, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'organizer_id', nullable: false)]
    private ?User $organizer = null;

    #[ORM\Column(type: 'string', columnDefinition: "ENUM('UPCOMING','ONGOING','COMPLETED','CANCELLED')", options: ['default' => 'UPCOMING'])]
    private ?string $status = 'UPCOMING';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventParticipation::class, cascade: ['remove'])]
    private Collection $participations;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventLike::class, cascade: ['remove'])]
    private Collection $likes;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventComment::class, cascade: ['remove'])]
    private Collection $comments;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->participations = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->comments = new ArrayCollection();
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
    public function setDescription(?string $d): static
    {
        $this->description = $d;
        return $this;
    }
    public function getEventType(): ?string
    {
        return $this->eventType;
    }
    public function setEventType(string $t): static
    {
        $this->eventType = $t;
        return $this;
    }
    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }
    public function setEventDate(\DateTimeInterface $d): static
    {
        $this->eventDate = $d;
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
    public function getLocation(): ?string
    {
        return $this->location;
    }
    public function setLocation(?string $l): static
    {
        $this->location = $l;
        return $this;
    }
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }
    public function setLatitude(?float $l): static
    {
        $this->latitude = $l;
        return $this;
    }
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }
    public function setLongitude(?float $l): static
    {
        $this->longitude = $l;
        return $this;
    }
    public function isOnline(): bool
    {
        return $this->isOnline;
    }
    public function setIsOnline(bool $o): static
    {
        $this->isOnline = $o;
        return $this;
    }
    public function getOnlineLink(): ?string
    {
        return $this->onlineLink;
    }
    public function setOnlineLink(?string $l): static
    {
        $this->onlineLink = $l;
        return $this;
    }
    public function getMaxCapacity(): int
    {
        return $this->maxCapacity;
    }
    public function setMaxCapacity(int $c): static
    {
        $this->maxCapacity = $c;
        return $this;
    }
    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }
    public function setCoverImage(?string $i): static
    {
        $this->coverImage = $i;
        return $this;
    }
    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }
    public function setOrganizer(?User $o): static
    {
        $this->organizer = $o;
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
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
    /** @return Collection<int, EventParticipation> */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }
    /** @return Collection<int, EventLike> */
    public function getLikes(): Collection
    {
        return $this->likes;
    }
    /** @return Collection<int, EventComment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }
}
