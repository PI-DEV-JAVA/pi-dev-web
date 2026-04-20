<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'notifications')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // OFFER_DECISION, REPLY, INTERVIEW, ACTIVITY, EVENT, GENERAL

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(name: 'is_read', type: 'boolean', options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $u): static { $this->user = $u; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $t): static { $this->title = $t; return $this; }
    public function getMessage(): ?string { return $this->message; }
    public function setMessage(?string $m): static { $this->message = $m; return $this; }
    public function getLink(): ?string { return $this->link; }
    public function setLink(?string $l): static { $this->link = $l; return $this; }
    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $r): static { $this->isRead = $r; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
}
