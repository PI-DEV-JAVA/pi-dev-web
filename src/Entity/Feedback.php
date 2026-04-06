<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\FeedbackRepository::class)]
#[ORM\Table(name: 'feedback')]
class Feedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_feedback', type: 'integer')]
    private ?int $idFeedback = null;

    #[ORM\OneToOne(inversedBy: 'feedback', targetEntity: Participation::class)]
    #[ORM\JoinColumn(name: 'id_participation', referencedColumnName: 'id_participation', nullable: false)]
    #[Assert\NotNull(message: 'La participation est obligatoire.')]
    private ?Participation $participation = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: 'La note est obligatoire.')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'La note doit être comprise entre {{ min }} et {{ max }} étoiles.')]
    private ?int $note = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(min: 5, max: 1000, minMessage: 'Le commentaire doit faire au moins {{ limit }} caractères.', maxMessage: 'Le commentaire est trop long.')]
    private ?string $commentaire = null;

    #[ORM\Column(name: 'date_feedback', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateFeedback = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private ?bool $recommanderait = true;

    public function __construct()
    {
        $this->dateFeedback = new \DateTime();
    }

    public function getIdFeedback(): ?int
    {
        return $this->idFeedback;
    }

    public function getParticipation(): ?Participation
    {
        return $this->participation;
    }

    public function setParticipation(Participation $participation): static
    {
        $this->participation = $participation;
        return $this;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getDateFeedback(): ?\DateTimeInterface
    {
        return $this->dateFeedback;
    }

    public function setDateFeedback(?\DateTimeInterface $dateFeedback): static
    {
        $this->dateFeedback = $dateFeedback;
        return $this;
    }

    public function isRecommanderait(): ?bool
    {
        return $this->recommanderait;
    }

    public function setRecommanderait(?bool $recommanderait): static
    {
        $this->recommanderait = $recommanderait;
        return $this;
    }
}
