<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\PresenceRepository::class)]
#[ORM\Table(name: 'presence')]
class Presence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_presence', type: 'integer')]
    private ?int $idPresence = null;

    #[ORM\OneToOne(inversedBy: 'presence', targetEntity: Participation::class)]
    #[ORM\JoinColumn(name: 'id_participation', referencedColumnName: 'id_participation', nullable: false)]
    private ?Participation $participation = null;

    #[ORM\Column(name: 'est_present', type: 'boolean', options: ['default' => false])]
    private ?bool $estPresent = false;

    #[ORM\Column(name: 'date_scan', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateScan = null;

    #[ORM\Column(name: 'code_qr', type: 'string', length: 255, nullable: true)]
    private ?string $codeQr = null;

    public function getIdPresence(): ?int
    {
        return $this->idPresence;
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

    public function isEstPresent(): ?bool
    {
        return $this->estPresent;
    }

    public function setEstPresent(bool $estPresent): static
    {
        $this->estPresent = $estPresent;
        return $this;
    }

    public function getDateScan(): ?\DateTimeInterface
    {
        return $this->dateScan;
    }

    public function setDateScan(?\DateTimeInterface $dateScan): static
    {
        $this->dateScan = $dateScan;
        return $this;
    }

    public function getCodeQr(): ?string
    {
        return $this->codeQr;
    }

    public function setCodeQr(?string $codeQr): static
    {
        $this->codeQr = $codeQr;
        return $this;
    }
}
