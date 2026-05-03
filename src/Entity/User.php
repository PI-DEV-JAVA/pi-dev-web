<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 190, unique: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'password_hash', length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', columnDefinition: "ENUM('ADMIN','HR','CANDIDATE')")]
    private ?string $role = 'CANDIDATE';

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'auth_provider', type: 'string', columnDefinition: "ENUM('LOCAL','GOOGLE')")]
    private ?string $authProvider = 'LOCAL';

    #[ORM\Column(name: 'provider_id', length: 255, nullable: true)]
    private ?string $providerId = null;

    #[ORM\Column(name: 'email_verified', type: 'boolean')]
    private bool $emailVerified = false;

    #[ORM\Column(name: 'failed_attempts', type: 'integer', options: ['default' => 0])]
    private int $failedAttempts = 0;

    #[ORM\Column(name: 'reset_token', length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(name: 'reset_token_expires_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(name: 'verification_token', length: 100, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Profile::class, cascade: ['persist', 'remove'])]
    private ?Profile $profile = null;

    #[ORM\Column(name: 'points_balance', type: 'integer', options: ['default' => 0])]
    private int $pointsBalance = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(?string $password): static { $this->password = $password; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): static { $this->active = $active; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getAuthProvider(): ?string { return $this->authProvider; }
    public function setAuthProvider(string $authProvider): static { $this->authProvider = $authProvider; return $this; }

    public function getProviderId(): ?string { return $this->providerId; }
    public function setProviderId(?string $providerId): static { $this->providerId = $providerId; return $this; }

    public function isEmailVerified(): bool { return $this->emailVerified; }
    public function setEmailVerified(bool $emailVerified): static { $this->emailVerified = $emailVerified; return $this; }

    public function getFailedAttempts(): int { return $this->failedAttempts; }
    public function setFailedAttempts(int $failedAttempts): static { $this->failedAttempts = $failedAttempts; return $this; }

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $resetToken): static { $this->resetToken = $resetToken; return $this; }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface { return $this->resetTokenExpiresAt; }
    public function setResetTokenExpiresAt(?\DateTimeInterface $dt): static { $this->resetTokenExpiresAt = $dt; return $this; }

    public function getVerificationToken(): ?string { return $this->verificationToken; }
    public function setVerificationToken(?string $token): static { $this->verificationToken = $token; return $this; }

    public function getProfile(): ?Profile { return $this->profile; }
    public function setProfile(?Profile $profile): static
    {
        if ($profile !== null && $profile->getUser() !== $this) {
            $profile->setUser($this);
        }
        $this->profile = $profile;
        return $this;
    }

    public function getPointsBalance(): int { return $this->pointsBalance; }
    public function setPointsBalance(int $p): static { $this->pointsBalance = $p; return $this; }
    public function addPoints(int $p): static { $this->pointsBalance += $p; return $this; }
    public function deductPoints(int $p): static { $this->pointsBalance = max(0, $this->pointsBalance - $p); return $this; }

    // === UserInterface ===
    public function getRoles(): array
    {
        return ['ROLE_' . ($this->role ?? 'CANDIDATE')];
    }

    public function getUserIdentifier(): string { return (string) $this->email; }
    public function eraseCredentials(): void {}
}
