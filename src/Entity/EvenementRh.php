<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\EvenementRhRepository::class)]
#[ORM\Table(name: 'evenement_rh')]
class EvenementRh
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_event', type: 'integer')]
    private ?int $idEvent = null;

    #[ORM\Column(name: 'Titre', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le titre de l\'événement est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $titre = null;

    #[ORM\Column(name: 'type_event', type: 'string', length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Veuillez spécifier le type d\'événement.')]
    private ?string $typeEvent = null;

    #[ORM\Column(name: 'date_event', type: 'date', nullable: true)]
    #[Assert\NotBlank(message: 'La date est obligatoire.')]
    #[Assert\GreaterThan('today', message: 'La date de l\'événement doit être ultérieure à aujourd\'hui.')]
    private ?\DateTimeInterface $dateEvent = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Le lieu est obligatoire.')]
    private ?string $lieu = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Choice(choices: ['Planifié', 'En cours', 'Terminé', 'Annulé'], message: 'Le statut sélectionné est invalide.')]
    private ?string $statut = null;

    #[ORM\OneToMany(mappedBy: 'evenement', targetEntity: Participation::class, cascade: ['remove'])]
    private Collection $participations;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
    }

    public function getIdEvent(): ?int
    {
        return $this->idEvent;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getTypeEvent(): ?string
    {
        return $this->typeEvent;
    }

    public function setTypeEvent(?string $typeEvent): static
    {
        $this->typeEvent = $typeEvent;
        return $this;
    }

    public function getDateEvent(): ?\DateTimeInterface
    {
        return $this->dateEvent;
    }

    public function setDateEvent(?\DateTimeInterface $dateEvent): static
    {
        $this->dateEvent = $dateEvent;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;
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

    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setEvenement($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            if ($participation->getEvenement() === $this) {
                $participation->setEvenement(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->titre;
    }
}
