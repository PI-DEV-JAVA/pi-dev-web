<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_participation')]
#[ORM\UniqueConstraint(name: 'unique_participation', columns: ['event_id', 'user_id'])]
class EventParticipation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'event_id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', columnDefinition: "ENUM('CONFIRMED','PENDING','CANCELLED','ATTENDED')", options: ['default' => 'PENDING'])]
    private ?string $status = 'PENDING';

    #[ORM\Column(name: 'registered_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $registeredAt = null;

    #[ORM\Column(name: 'qr_code', length: 500, nullable: true)]
    private ?string $qrCode = null;

    public function __construct()
    {
        $this->registeredAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getEvent(): ?Event
    {
        return $this->event;
    }
    public function setEvent(?Event $e): static
    {
        $this->event = $e;
        return $this;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $u): static
    {
        $this->user = $u;
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
    public function getRegisteredAt(): ?\DateTimeInterface
    {
        return $this->registeredAt;
    }
    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }
    public function setQrCode(?string $q): static
    {
        $this->qrCode = $q;
        return $this;
    }
}
