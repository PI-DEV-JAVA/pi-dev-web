<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\ParticipationRepository::class)]
#[ORM\Table(name: 'participation')]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_participation', type: 'integer')]
    private ?int $idParticipation = null;

    #[ORM\ManyToOne(targetEntity: EvenementRh::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'id_event', referencedColumnName: 'id_event', nullable: false)]
    #[Assert\NotNull(message: 'L\'événement est obligatoire.')]
    private ?EvenementRh $evenement = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'Le participant est obligatoire.')]
    private ?User $user = null;

    #[ORM\Column(name: 'Statut', type: 'string', length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Le statut de participation est requis.')]
    #[Assert\Choice(choices: ['Inscrit', 'Confirmé', 'Annulé'], message: 'Statut invalide.')]
    private ?string $statut = null;

    #[ORM\OneToOne(mappedBy: 'participation', targetEntity: Presence::class, cascade: ['persist', 'remove'])]
    private ?Presence $presence = null;

    #[ORM\OneToOne(mappedBy: 'participation', targetEntity: Feedback::class, cascade: ['persist', 'remove'])]
    private ?Feedback $feedback = null;

    public function getIdParticipation(): ?int
    {
        return $this->idParticipation;
    }

    public function getEvenement(): ?EvenementRh
    {
        return $this->evenement;
    }

    public function setEvenement(?EvenementRh $evenement): static
    {
        $this->evenement = $evenement;
        return $this;
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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getPresence(): ?Presence
    {
        return $this->presence;
    }

    public function setPresence(Presence $presence): static
    {
        // set the owning side of the relation if necessary
        if ($presence->getParticipation() !== $this) {
            $presence->setParticipation($this);
        }

        $this->presence = $presence;
        return $this;
    }

    public function getFeedback(): ?Feedback
    {
        return $this->feedback;
    }

    public function setFeedback(Feedback $feedback): static
    {
        // set the owning side of the relation if necessary
        if ($feedback->getParticipation() !== $this) {
            $feedback->setParticipation($this);
        }

        $this->feedback = $feedback;
        return $this;
    }
}
