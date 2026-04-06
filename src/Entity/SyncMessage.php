<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sync_messages')]
class SyncMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Sync::class)]
    #[ORM\JoinColumn(name: 'sync_id', nullable: false)]
    private ?Sync $sync = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sender_id', nullable: false)]
    private ?User $sender = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(name: 'is_read', type: 'boolean', options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getSync(): ?Sync
    {
        return $this->sync;
    }
    public function setSync(?Sync $s): static
    {
        $this->sync = $s;
        return $this;
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
    public function getMessage(): ?string
    {
        return $this->message;
    }
    public function setMessage(string $m): static
    {
        $this->message = $m;
        return $this;
    }
    public function isRead(): bool
    {
        return $this->isRead;
    }
    public function setIsRead(bool $r): static
    {
        $this->isRead = $r;
        return $this;
    }
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
