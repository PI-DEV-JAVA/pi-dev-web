<?php

namespace App\Tests\Entity;

use App\Entity\SupportTicket;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class SupportTicketTest extends TestCase
{
    private SupportTicket $ticket;

    protected function setUp(): void
    {
        $this->ticket = new SupportTicket();
    }

    public function testDefaultStatus(): void
    {
        $this->assertEquals('OPEN', $this->ticket->getStatus());
    }

    public function testDefaultPriority(): void
    {
        $this->assertEquals('MEDIUM', $this->ticket->getPriority());
    }

    public function testSetGetSubject(): void
    {
        $this->ticket->setSubject('Problème de connexion');
        $this->assertEquals('Problème de connexion', $this->ticket->getSubject());
    }

    public function testSetGetDescription(): void
    {
        $this->ticket->setDescription('Impossible de me connecter depuis ce matin');
        $this->assertStringContainsString('connecter', $this->ticket->getDescription());
    }

    public function testSetGetUser(): void
    {
        $user = new User();
        $this->ticket->setUser($user);
        $this->assertSame($user, $this->ticket->getUser());
    }

    public function testSetStatusClosed(): void
    {
        $this->ticket->setStatus('CLOSED');
        $this->assertEquals('CLOSED', $this->ticket->getStatus());
    }

    public function testSetStatusInProgress(): void
    {
        $this->ticket->setStatus('IN_PROGRESS');
        $this->assertEquals('IN_PROGRESS', $this->ticket->getStatus());
    }

    public function testSetPriorityHigh(): void
    {
        $this->ticket->setPriority('HIGH');
        $this->assertEquals('HIGH', $this->ticket->getPriority());
    }

    public function testSetPriorityLow(): void
    {
        $this->ticket->setPriority('LOW');
        $this->assertEquals('LOW', $this->ticket->getPriority());
    }

    public function testSetGetCategory(): void
    {
        $this->ticket->setCategory('ACCOUNT');
        $this->assertEquals('ACCOUNT', $this->ticket->getCategory());
    }

    public function testRepliesCollectionInitialized(): void
    {
        $this->assertCount(0, $this->ticket->getReplies());
    }
}
