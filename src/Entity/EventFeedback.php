<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_feedback')]
class EventFeedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: EventParticipation::class)]
    #[ORM\JoinColumn(name: 'participation_id', nullable: false, onDelete: 'CASCADE')]
    private ?EventParticipation $participation = null;

    #[ORM\Column(type: 'integer')]
    private int $rating = 5;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(name: 'would_recommend', type: 'boolean', options: ['default' => true])]
    private bool $wouldRecommend = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getParticipation(): ?EventParticipation { return $this->participation; }
    public function setParticipation(?EventParticipation $p): static { $this->participation = $p; return $this; }

    public function getRating(): int { return $this->rating; }
    public function setRating(int $r): static { $this->rating = $r; return $this; }

    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $c): static { $this->comment = $c; return $this; }

    public function isWouldRecommend(): bool { return $this->wouldRecommend; }
    public function setWouldRecommend(bool $w): static { $this->wouldRecommend = $w; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
}
