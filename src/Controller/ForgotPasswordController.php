<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ForgotPasswordController extends AbstractController
{
    /**
     * Step 1: Enter email → send 6-digit code
     * Step 2: Verify OTP code
     * Step 3: Set new password
     */
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, EntityManagerInterface $em, MailerInterface $mailer, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $session = $request->getSession();
        $step = $session->get('fp_step', 1);
        $error = null;
        $success = null;

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'send_code') {
                // STEP 1: Send OTP code
                $email = trim($request->request->get('email', ''));
                if (empty($email)) {
                    $error = 'Veuillez entrer votre adresse email.';
                } else {
                    $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                    if (!$user || $user->getAuthProvider() !== 'LOCAL') {
                        $error = 'Aucun compte trouvé avec cet email.';
                    } else {
                        // Generate 6-digit OTP
                        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                        $expiresAt = new \DateTime('+10 minutes');

                        // Store in DB
                        $user->setResetToken($code);
                        $user->setResetTokenExpiresAt($expiresAt);
                        $em->flush();

                        // Send email (same HTML as JavaFX EmailService)
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
                                . '<p style="color: #6b7280; font-size: 14px; margin: 0 0 24px;">Réinitialisation de mot de passe</p>'
                                . '<div style="background: #f3f4f6; border-radius: 12px; padding: 20px; margin: 0 0 24px;">'
                                . '<p style="color: #9ca3af; font-size: 12px; margin: 0 0 8px;">Votre code de vérification</p>'
                                . '<h2 style="color: #6366f1; font-size: 36px; letter-spacing: 8px; margin: 0; font-weight: 800;">' . $code . '</h2>'
                                . '</div>'
                                . '<p style="color: #9ca3af; font-size: 12px; margin: 0;">Ce code expire dans 10 minutes.</p>'
                                . '</div></div>'
                            );

                        try {
                            $mailer->send($mail);
                            $session->set('fp_step', 2);
                            $session->set('fp_email', $email);
                            $session->set('fp_expires', $expiresAt->getTimestamp());
                            $step = 2;
                            $success = 'Code envoyé à ' . $email;
                        } catch (\Exception $e) {
                            $error = 'Erreur email: ' . $e->getMessage();
                        }
                    }
                }
            } elseif ($action === 'verify_code') {
                // STEP 2: Verify OTP
                $code = trim($request->request->get('code', ''));
                $email = $session->get('fp_email');
                $expires = $session->get('fp_expires', 0);

                if (empty($code)) {
                    $error = 'Veuillez entrer le code à 6 chiffres.';
                    $step = 2;
                } elseif (time() > $expires) {
                    $error = 'Le code a expiré. Veuillez en demander un nouveau.';
                    $session->set('fp_step', 1);
                    $step = 1;
                } else {
                    $user = $em->getRepository(User::class)->findOneBy(['email' => $email, 'resetToken' => $code]);
                    if (!$user) {
                        $error = 'Code invalide. Vérifiez et réessayez.';
                        $step = 2;
                    } else {
                        // OTP verified!
                        $session->set('fp_step', 3);
                        $session->set('fp_verified', true);
                        $step = 3;
                        $success = 'Code vérifié ! Créez votre nouveau mot de passe.';
                    }
                }
            } elseif ($action === 'reset_password') {
                // STEP 3: Set new password
                $email = $session->get('fp_email');
                $verified = $session->get('fp_verified', false);

                if (!$verified || !$email) {
                    $session->remove('fp_step');
                    return $this->redirectToRoute('app_forgot_password');
                }

                $password = $request->request->get('password', '');
                $confirm = $request->request->get('confirm_password', '');

                if (strlen($password) < 6) {
                    $error = 'Le mot de passe doit contenir au moins 6 caractères.';
                    $step = 3;
                } elseif ($password !== $confirm) {
                    $error = 'Les mots de passe ne correspondent pas.';
                    $step = 3;
                } else {
                    $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                    if ($user) {
                        $user->setPassword($passwordHasher->hashPassword($user, $password));
                        $user->setResetToken(null);
                        $user->setResetTokenExpiresAt(null);
                        $user->setFailedAttempts(0);
                        $user->setActive(true); // Unlock if locked
                        $em->flush();

                        // Clear session
                        $session->remove('fp_step');
                        $session->remove('fp_email');
                        $session->remove('fp_expires');
                        $session->remove('fp_verified');

                        $this->addFlash('success', 'Mot de passe réinitialisé avec succès ! Connectez-vous.');
                        return $this->redirectToRoute('app_login');
                    }
                }
            }
        }

        // Reset to step 1 on GET
        if ($request->isMethod('GET')) {
            $session->remove('fp_step');
            $step = 1;
        }

        return $this->render('front/security/forgot_password.html.twig', [
            'step' => $step,
            'error' => $error,
            'success' => $success,
            'fp_email' => $session->get('fp_email', ''),
            'fp_expires' => $session->get('fp_expires', 0),
        ]);
    }
}
