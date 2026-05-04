<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'profile_view')]
#[ORM\Index(columns: ['viewed_user_id'], name: 'idx_viewed_user')]
class ProfileView
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** The user whose profile was viewed */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $viewedUser;

    /** The user who viewed the profile (null = anonymous) */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $viewer = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $viewedAt;

    public function __construct()
    {
        $this->viewedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getViewedUser(): User { return $this->viewedUser; }
    public function setViewedUser(User $user): self { $this->viewedUser = $user; return $this; }

    public function getViewer(): ?User { return $this->viewer; }
    public function setViewer(?User $viewer): self { $this->viewer = $viewer; return $this; }

    public function getViewedAt(): \DateTimeInterface { return $this->viewedAt; }
}
