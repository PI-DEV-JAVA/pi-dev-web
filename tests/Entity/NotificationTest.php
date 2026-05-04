<?php

namespace App\Tests\Entity;

use App\Entity\Notification;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    private Notification $notif;

    protected function setUp(): void
    {
        $this->notif = new Notification();
    }

    public function testSetGetTitle(): void
    {
        $this->notif->setTitle('Nouvelle candidature');
        $this->assertEquals('Nouvelle candidature', $this->notif->getTitle());
    }

    public function testSetGetMessage(): void
    {
        $this->notif->setMessage('Vous avez reçu une candidature pour Dev PHP');
        $this->assertStringContainsString('candidature', $this->notif->getMessage());
    }

    public function testSetGetUser(): void
    {
        $user = new User();
        $this->notif->setUser($user);
        $this->assertSame($user, $this->notif->getUser());
    }
}
