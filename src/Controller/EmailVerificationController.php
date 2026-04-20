<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class EmailVerificationController extends AbstractController
{
    /**
     * Code-based email verification page (after registration).
     * User enters the 6-digit code received by email.
     */
    #[Route('/verify-email', name: 'app_verify_email_code', methods: ['GET', 'POST'])]
    public function verifyCode(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $session = $request->getSession();
        $email = $session->get('verify_email');
        $expires = $session->get('verify_expires', 0);

        if (!$email) {
            $this->addFlash('danger', 'Session expirée. Veuillez vous réinscrire.');
            return $this->redirectToRoute('app_register');
        }

        $error = null;
        $success = null;

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action', 'verify');

            if ($action === 'resend') {
                // Resend OTP
                $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($user) {
                    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $user->setVerificationToken($code);
                    $user->setResetTokenExpiresAt(new \DateTime('+10 minutes'));
                    $em->flush();

                    $session->set('verify_expires', (new \DateTime('+10 minutes'))->getTimestamp());
                    $expires = $session->get('verify_expires');

                    $mail = (new Email())
                        ->from('talentos.pidev@gmail.com')
                        ->to($email)
                        ->subject('🔐 Talentos — Code de vérification')
                        ->html(
                            '<div style="font-family: \'Segoe UI\', Arial, sans-serif; max-width: 480px; margin: 0 auto;'
                            . 'padding: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);'
                            . 'border-radius: 16px;">'
                            . '<div style="background: white; border-radius: 12px; padding: 32px; text-align: center;">'
                            . '<h1 style="color: #111827; font-size: 24px; margin: 0 0 8px;">Talentos</h1>'
                            . '<p style="color: #6b7280; font-size: 14px; margin: 0 0 24px;">Vérification de votre email</p>'
                            . '<div style="background: #f3f4f6; border-radius: 12px; padding: 20px; margin: 0 0 24px;">'
                            . '<p style="color: #9ca3af; font-size: 12px; margin: 0 0 8px;">Votre code de vérification</p>'
                            . '<h2 style="color: #6366f1; font-size: 36px; letter-spacing: 8px; margin: 0; font-weight: 800;">' . $code . '</h2>'
                            . '</div>'
                            . '<p style="color: #9ca3af; font-size: 12px; margin: 0;">Ce code expire dans 10 minutes.</p>'
                            . '</div></div>'
                        );

                    try {
                        $mailer->send($mail);
                        $success = 'Nouveau code envoyé à ' . $email;
                    } catch (\Exception $e) {
                        $error = 'Erreur lors de l\'envoi. Réessayez.';
                    }
                }
            } else {
                // Verify code
                $code = trim($request->request->get('code', ''));

                if (empty($code)) {
                    $error = 'Veuillez entrer le code à 6 chiffres.';
                } elseif (time() > $expires) {
                    $error = 'Le code a expiré. Cliquez sur "Renvoyer" pour en obtenir un nouveau.';
                } else {
                    $user = $em->getRepository(User::class)->findOneBy([
                        'email' => $email,
                        'verificationToken' => $code,
                    ]);

                    if (!$user) {
                        $error = 'Code invalide. Vérifiez et réessayez.';
                    } else {
                        $user->setEmailVerified(true);
                        $user->setVerificationToken(null);
                        $user->setResetTokenExpiresAt(null);
                        $em->flush();

                        $session->remove('verify_email');
                        $session->remove('verify_expires');

                        $this->addFlash('success', 'Email vérifié avec succès ! Vous pouvez maintenant vous connecter.');
                        return $this->redirectToRoute('app_login');
                    }
                }
            }
        }

        return $this->render('front/security/verify_email.html.twig', [
            'email' => $email,
            'expires' => $expires,
            'error' => $error,
            'success' => $success,
        ]);
    }

    /**
     * Legacy link-based verification (keep for backward compatibility).
     */
    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyLink(string $token, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('danger', 'Lien de vérification invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        $user->setEmailVerified(true);
        $user->setVerificationToken(null);
        $em->flush();

        $this->addFlash('success', 'Email vérifié avec succès ! Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }
}
