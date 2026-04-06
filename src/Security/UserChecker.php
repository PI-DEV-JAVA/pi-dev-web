<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User)
            return;

        // Check account lock (5 failed attempts)
        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte est verrouillé suite à trop de tentatives échouées. Utilisez "Mot de passe oublié" pour le déverrouiller.'
            );
        }

        // Check email verification (only for LOCAL auth, not Google)
        if ($user->getAuthProvider() === 'LOCAL' && !$user->isEmailVerified()) {
            throw new CustomUserMessageAccountStatusException(
                'Veuillez vérifier votre adresse email avant de vous connecter. Vérifiez votre boîte de réception.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Nothing to check post-auth
    }
}
