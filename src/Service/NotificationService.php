<?php
namespace App\Service;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private EntityManagerInterface $em;
    public function __construct(EntityManagerInterface $em) { $this->em = $em; }

    public function notify(User $user, string $type, string $title, ?string $msg = null, ?string $link = null): void
    {
        $n = new Notification();
        $n->setUser($user)->setType($type)->setTitle($title)->setMessage($msg)->setLink($link);
        $this->em->persist($n);
        $this->em->flush();
    }

    public function getUnreadCount(User $user): int
    {
        return $this->em->getRepository(Notification::class)->count(['user' => $user, 'isRead' => false]);
    }
}
