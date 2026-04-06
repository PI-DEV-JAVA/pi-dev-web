<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $error = null;
        $success = null;

        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $confirmPassword = $request->request->get('confirm_password', '');
            $role = $request->request->get('role', 'CANDIDATE');

            // CAPTCHA: simple math
            $captchaAnswer = $request->request->get('captcha', '');
            $captchaExpected = $request->getSession()->get('captcha_answer');

            if (empty($email) || empty($password)) {
                $error = 'Email et mot de passe sont obligatoires.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Les mots de passe ne correspondent pas.';
            } elseif (strlen($password) < 6) {
                $error = 'Le mot de passe doit contenir au moins 6 caractères.';
            } elseif ($captchaAnswer != $captchaExpected) {
                $error = 'CAPTCHA incorrect.';
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

                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);

                    // Create empty profile
                    $profile = new Profile();
                    $profile->setUser($user);
                    $profile->setProfileCompleted(false);

                    $entityManager->persist($user);
                    $entityManager->persist($profile);
                    $entityManager->flush();

                    $success = 'Compte créé avec succès ! Vous pouvez vous connecter.';
                }
            }
        }

        // Generate CAPTCHA
        $a = random_int(1, 20);
        $b = random_int(1, 20);
        $request->getSession()->set('captcha_answer', $a + $b);

        return $this->render('front/security/register.html.twig', [
            'error' => $error,
            'success' => $success,
            'captcha_question' => "$a + $b = ?",
        ]);
    }
}
