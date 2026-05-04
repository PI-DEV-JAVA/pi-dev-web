<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
#[ORM\Table(name: 'profiles')]
class Profile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'profile', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', unique: true)]
    private ?User $user = null;

    #[ORM\Column(name: 'first_name', length: 50, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', length: 50, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'birth_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(name: 'phone_number', length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(name: 'professional_title', length: 100, nullable: true)]
    private ?string $professionalTitle = null;

    #[ORM\Column(name: 'years_of_experience', nullable: true)]
    private ?int $yearsOfExperience = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(name: 'profile_completed', type: 'boolean')]
    private bool $profileCompleted = false;

    #[ORM\Column(name: 'profile_picture_path', length: 500, nullable: true)]
    private ?string $profilePicturePath = null;

    #[ORM\Column(name: 'cv_path', length: 500, nullable: true)]
    private ?string $cvPath = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $skills = [];

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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }
    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }
    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }
    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }
    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }
    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getProfessionalTitle(): ?string
    {
        return $this->professionalTitle;
    }
    public function setProfessionalTitle(?string $professionalTitle): static
    {
        $this->professionalTitle = $professionalTitle;
        return $this;
    }

    public function getYearsOfExperience(): ?int
    {
        return $this->yearsOfExperience;
    }
    public function setYearsOfExperience(?int $yearsOfExperience): static
    {
        $this->yearsOfExperience = $yearsOfExperience;
        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }
    public function setSummary(?string $summary): static
    {
        $this->summary = $summary;
        return $this;
    }

    public function isProfileCompleted(): bool
    {
        return $this->profileCompleted;
    }
    public function setProfileCompleted(bool $profileCompleted): static
    {
        $this->profileCompleted = $profileCompleted;
        return $this;
    }

    public function getProfilePicturePath(): ?string
    {
        return $this->profilePicturePath;
    }
    public function setProfilePicturePath(?string $profilePicturePath): static
    {
        $this->profilePicturePath = $profilePicturePath;
        return $this;
    }

    public function getCvPath(): ?string
    {
        return $this->cvPath;
    }
    public function setCvPath(?string $cvPath): static
    {
        $this->cvPath = $cvPath;
        return $this;
    }

    public function getSkills(): array
    {
        return $this->skills ?? [];
    }
    public function setSkills(?array $skills): static
    {
        $this->skills = $skills ?? [];
        return $this;
    }

    /**
     * Calculate profile completion percentage (0-100).
     */
    public function getCompletionPercent(): int
    {
        $fields = [
            $this->firstName,
            $this->lastName,
            $this->birthDate,
            $this->phoneNumber,
            $this->location,
            $this->professionalTitle,
            $this->yearsOfExperience !== null ? 'set' : null,
            $this->summary,
            $this->profilePicturePath,
            $this->cvPath,
        ];
        $filled = 0;
        foreach ($fields as $f) {
            if ($f !== null && $f !== '' && $f !== []) $filled++;
        }
        return (int)round($filled / count($fields) * 100);
    }
}
