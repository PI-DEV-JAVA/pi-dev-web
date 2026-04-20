<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'meets')]
#[ApiResource(
    normalizationContext: ['groups' => ['meet:read']],
    denormalizationContext: ['groups' => ['meet:write']]
)]
class Meet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['meet:read', 'interview:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Interview::class, inversedBy: 'meets')]
    #[ORM\JoinColumn(name: 'interview_id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['meet:read', 'meet:write'])]
    private ?Interview $interview = null;

    #[ORM\Column(name: 'title', length: 150)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(min: 3, max: 150, minMessage: 'Le titre doit faire au moins {{ limit }} caractères', maxMessage: 'Le titre ne peut pas faire plus de {{ limit }} caractères')]
    #[Groups(['meet:read', 'meet:write', 'interview:read'])]
    private ?string $title = null;

    #[ORM\Column(name: 'meet_date', type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'La date est obligatoire')]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\GreaterThanOrEqual('today', message: 'La date de rencontre doit être dans le futur.')]
    #[Groups(['meet:read', 'meet:write', 'interview:read'])]
    private ?\DateTimeInterface $meetDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'Les notes ne peuvent pas dépasser {{ limit }} caractères.')]
    #[Groups(['meet:read', 'meet:write'])]
    private ?string $notes = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\Range(min: 0, max: 20, notInRangeMessage: 'La note doit être entre {{ min }} et {{ max }}.')]
    #[Groups(['meet:read', 'meet:write'])]
    private ?float $grade = null;

    #[ORM\Column(name: 'room_id', length: 50, unique: true, nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['meet:read', 'meet:write', 'interview:read'])]
    private ?string $roomId = null;

    #[ORM\Column(name: 'meet_type', length: 50, options: ["default" => "GENERAL"])]
    #[Groups(['meet:read', 'meet:write', 'interview:read'])]
    private ?string $meetType = 'GENERAL';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInterview(): ?Interview
    {
        return $this->interview;
    }
    public function setInterview(?Interview $interview): static
    {
        $this->interview = $interview;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getMeetDate(): ?\DateTimeInterface
    {
        return $this->meetDate;
    }
    public function setMeetDate(?\DateTimeInterface $meetDate): static
    {
        $this->meetDate = $meetDate;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }
    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getGrade(): ?float
    {
        return $this->grade;
    }
    public function setGrade(?float $grade): static
    {
        $this->grade = $grade;
        return $this;
    }

    public function getRoomId(): ?string
    {
        return $this->roomId;
    }
    public function setRoomId(?string $roomId): static
    {
        $this->roomId = $roomId;
        return $this;
    }

    public function getMeetType(): ?string
    {
        return $this->meetType;
    }

    public function setMeetType(?string $meetType): static
    {
        $this->meetType = $meetType;
        return $this;
    }
}
