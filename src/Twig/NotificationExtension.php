<?php
namespace App\Twig;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NotificationExtension extends AbstractExtension implements GlobalsInterface
{
    private EntityManagerInterface $em;
    private Security $security;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    public function getGlobals(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return ['notif_unread_count' => 0, 'notif_latest' => []];
        }

        $repo = $this->em->getRepository(Notification::class);
        $unread = $repo->count(['user' => $user, 'isRead' => false]);
        $latest = $repo->findBy(['user' => $user], ['createdAt' => 'DESC'], 8);

        return [
            'notif_unread_count' => $unread,
            'notif_latest' => $latest,
        ];
    }
}
