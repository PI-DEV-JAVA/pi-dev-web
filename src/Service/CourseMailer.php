<?php

namespace App\Service;

use App\Entity\Formation;
use App\Entity\FormationEnrollment;
use App\Entity\Quiz;
use App\Entity\Seance;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class CourseMailer
{
    public function __construct(
        private readonly MailerInterface        $mailer,
        private readonly Environment            $twig,
        private readonly EntityManagerInterface $em,
        private readonly string                 $fromEmail = 'noreply@talentos.tn'
    ) {}

    // ── Send to a single user ──────────────────────────────────────────────

    public function sendEnrollmentApproved(User $user, Formation $formation): void
    {
        $this->send(
            $user->getEmail(),
            '✅ Votre inscription à "' . $formation->getTitre() . '" a été approuvée !',
            $this->twig->render('emails/enrollment_approved.html.twig', [
                'user'      => $user,
                'formation' => $formation,
            ])
        );
    }

    public function sendEnrollmentRejected(User $user, Formation $formation): void
    {
        $this->send(
            $user->getEmail(),
            '❌ Votre candidature à "' . $formation->getTitre() . '" n\'a pas été retenue',
            $this->twig->render('emails/enrollment_rejected.html.twig', [
                'user'      => $user,
                'formation' => $formation,
            ])
        );
    }

    // ── Send to ALL approved enrollees ────────────────────────────────────

    public function sendNewSeance(Formation $formation, Seance $seance): void
    {
        $enrollees = $this->getApprovedEnrollees($formation);
        foreach ($enrollees as $user) {
            $this->send(
                $user->getEmail(),
                '📅 Nouvelle séance ajoutée : "' . $seance->getTitre() . '"',
                $this->twig->render('emails/new_seance.html.twig', [
                    'user'      => $user,
                    'formation' => $formation,
                    'seance'    => $seance,
                ])
            );
        }
    }

    public function sendQuizAvailable(Formation $formation, Quiz $quiz): void
    {
        $enrollees = $this->getApprovedEnrollees($formation);
        foreach ($enrollees as $user) {
            $this->send(
                $user->getEmail(),
                '🧠 Quiz disponible : "' . $quiz->getTitre() . '"',
                $this->twig->render('emails/quiz_available.html.twig', [
                    'user'      => $user,
                    'formation' => $formation,
                    'quiz'      => $quiz,
                ])
            );
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** @return User[] */
    private function getApprovedEnrollees(Formation $formation): array
    {
        $enrollments = $this->em->getRepository(FormationEnrollment::class)->findBy([
            'formation' => $formation,
            'status'    => FormationEnrollment::STATUS_APPROVED,
        ]);
        return array_map(fn(FormationEnrollment $e) => $e->getUser(), $enrollments);
    }

    private function send(string $to, string $subject, string $htmlBody): void
    {
        try {
            $email = (new Email())
                ->from($this->fromEmail)
                ->to($to)
                ->subject($subject)
                ->html($htmlBody);

            $this->mailer->send($email);
        } catch (\Throwable) {
            // Silent fail — never break the user flow because of email
        }
    }
}
