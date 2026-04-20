<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quiz_attempt')]
class QuizAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class)]
    #[ORM\JoinColumn(name: 'quiz_id', nullable: false)]
    private ?Quiz $quiz = null;

    /** Score out of 20 */
    #[ORM\Column(type: 'float', options: ['default' => 0])]
    private float $score = 0;

    #[ORM\Column(name: 'tab_switch_count', type: 'integer', options: ['default' => 0])]
    private int $tabSwitchCount = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $cheated = false;

    #[ORM\Column(name: 'submitted_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $submittedAt = null;

    public function __construct()
    {
        $this->submittedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $u): static { $this->user = $u; return $this; }

    public function getQuiz(): ?Quiz { return $this->quiz; }
    public function setQuiz(?Quiz $q): static { $this->quiz = $q; return $this; }

    public function getScore(): float { return $this->score; }
    public function setScore(float $s): static { $this->score = $s; return $this; }

    public function getTabSwitchCount(): int { return $this->tabSwitchCount; }
    public function setTabSwitchCount(int $c): static { $this->tabSwitchCount = $c; return $this; }

    public function isCheated(): bool { return $this->cheated; }
    public function setCheated(bool $c): static { $this->cheated = $c; return $this; }

    public function getSubmittedAt(): ?\DateTimeInterface { return $this->submittedAt; }
}
