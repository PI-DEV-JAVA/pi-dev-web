<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'syncs')]
class Sync
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sender_id', nullable: false)]
    private ?User $sender = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'receiver_id', nullable: false)]
    private ?User $receiver = null;

    #[ORM\Column(length: 50, options: ['default' => 'NETWORK'])]
    private string $reason = 'NETWORK';

    #[ORM\Column(length: 50, options: ['default' => 'PENDING'])]
    private string $status = 'PENDING';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'accepted_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $acceptedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getSender(): ?User
    {
        return $this->sender;
    }
    public function setSender(?User $s): static
    {
        $this->sender = $s;
        return $this;
    }
    public function getReceiver(): ?User
    {
        return $this->receiver;
    }
    public function setReceiver(?User $r): static
    {
        $this->receiver = $r;
        return $this;
    }
    public function getReason(): string
    {
        return $this->reason;
    }
    public function setReason(string $r): static
    {
        $this->reason = $r;
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
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
    public function getAcceptedAt(): ?\DateTimeInterface
    {
        return $this->acceptedAt;
    }
    public function setAcceptedAt(?\DateTimeInterface $a): static
    {
        $this->acceptedAt = $a;
        return $this;
    }

    public function getOtherUser(User $me): ?User
    {
        return $this->sender === $me ? $this->receiver : $this->sender;
    }

    public function getReasonEmoji(): string
    {
        return match ($this->reason) {
            'COLLABORATE' => '🤝', 'LEARN' => '📚', 'MENTOR' => '🎓', 'HIRE' => '💼', default => '🌐',
        };
    }
}
