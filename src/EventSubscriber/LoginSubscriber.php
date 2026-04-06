<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $email = $request->request->get('_username', '');

        if (empty($email))
            return;

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user)
            return;

        // Only count for active LOCAL accounts
        if (!$user->isActive())
            return;

        $attempts = $user->getFailedAttempts() + 1;
        $user->setFailedAttempts($attempts);

        $remaining = 5 - $attempts;

        // Lock account after 5 failed attempts
        if ($attempts >= 5) {
            $user->setActive(false);
            $request->getSession()->getFlashBag()->add('danger',
                'Compte verrouillé après 5 tentatives échouées. Utilisez "Mot de passe oublié" pour le déverrouiller.'
            );
        } else {
            $request->getSession()->getFlashBag()->add('warning',
                'Mot de passe incorrect. Il vous reste ' . $remaining . ' tentative' . ($remaining > 1 ? 's' : '') . ' avant le verrouillage.'
            );
        }

        $this->em->flush();
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if ($user instanceof User) {
            $user->setFailedAttempts(0);
            $this->em->flush();
        }
    }
}
