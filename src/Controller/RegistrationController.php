<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        HttpClientInterface $httpClient
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $error = null;
        $success = null;
        $recaptchaSiteKey = $this->getParameter('app.recaptcha_site_key');

        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $confirmPassword = $request->request->get('confirm_password', '');
            $role = $request->request->get('role', 'CANDIDATE');

            // reCAPTCHA v2 validation
            $recaptchaResponse = $request->request->get('g-recaptcha-response', '');
            $recaptchaValid = $this->verifyRecaptcha($recaptchaResponse, $httpClient);

            if (empty($email) || empty($password)) {
                $error = 'Email et mot de passe sont obligatoires.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Les mots de passe ne correspondent pas.';
            } elseif (strlen($password) < 6 || !preg_match('/\d/', $password) || !preg_match('/[a-zA-Z]/', $password) || !preg_match('/[A-Z]/', $password)) {
                $error = 'Le mot de passe doit contenir au moins 6 caractères, 1 chiffre, 1 lettre et 1 majuscule.';
            } elseif (!$recaptchaValid) {
                $error = 'Veuillez compléter le CAPTCHA.';
            } elseif (!in_array($role, ['CANDIDATE', 'HR'])) {
                $error = 'Rôle invalide.';
            } else {
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existingUser) {
                    $error = 'Cet email est déjà utilisé.';
                } else {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setRole($role);
                    $user->setAuthProvider('LOCAL');
                    $user->setActive(true);
                    $user->setEmailVerified(false);

                    // Generate 6-digit verification code (10 min expiry)
                    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $user->setVerificationToken($code);
                    $user->setResetTokenExpiresAt(new \DateTime('+10 minutes'));

                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);

                    // Create empty profile
                    $profile = new Profile();
                    $profile->setUser($user);
                    $profile->setProfileCompleted(false);

                    $entityManager->persist($user);
                    $entityManager->persist($profile);
                    $entityManager->flush();

                    // Send verification email with 6-digit code
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
                    } catch (\Exception $e) {
                        $request->getSession()->getFlashBag()->add('warning', 'Email non envoyé: ' . $e->getMessage());
                    }

                    // Store email + expiry in session for the verify page
                    $request->getSession()->set('verify_email', $email);
                    $request->getSession()->set('verify_expires', (new \DateTime('+10 minutes'))->getTimestamp());

                    return $this->redirectToRoute('app_verify_email_code');
                }
            }
        }

        return $this->render('front/security/register.html.twig', [
            'error' => $error,
            'success' => $success,
            'recaptcha_site_key' => $recaptchaSiteKey,
        ]);
    }

    /**
     * Verify reCAPTCHA v2 response with Google's API.
     */
    private function verifyRecaptcha(string $response, HttpClientInterface $httpClient): bool
    {
        if (empty($response)) {
            return false;
        }

        $secretKey = $this->getParameter('app.recaptcha_secret_key');

        try {
            $result = $httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $secretKey,
                    'response' => $response,
                ],
            ]);

            $data = $result->toArray();
            return $data['success'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
