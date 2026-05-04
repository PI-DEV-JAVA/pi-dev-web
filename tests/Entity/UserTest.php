<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Profile;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    // ── Constructor defaults ──
    public function testDefaultRole(): void
    {
        $this->assertEquals('CANDIDATE', $this->user->getRole());
    }

    public function testDefaultActive(): void
    {
        $this->assertTrue($this->user->isActive());
    }

    public function testDefaultEmailNotVerified(): void
    {
        $this->assertFalse($this->user->isEmailVerified());
    }

    public function testDefaultFailedAttempts(): void
    {
        $this->assertEquals(0, $this->user->getFailedAttempts());
    }

    public function testDefaultPointsBalance(): void
    {
        $this->assertEquals(0, $this->user->getPointsBalance());
    }

    public function testDefaultAuthProvider(): void
    {
        $this->assertEquals('LOCAL', $this->user->getAuthProvider());
    }

    // ── Setters / Getters ──
    public function testSetGetEmail(): void
    {
        $this->user->setEmail('test@talentos.tn');
        $this->assertEquals('test@talentos.tn', $this->user->getEmail());
    }

    public function testSetGetPassword(): void
    {
        $this->user->setPassword('hashed_password_123');
        $this->assertEquals('hashed_password_123', $this->user->getPassword());
    }

    public function testSetGetRole(): void
    {
        $this->user->setRole('RECRUITER');
        $this->assertEquals('RECRUITER', $this->user->getRole());
    }

    public function testSetGetActive(): void
    {
        $this->user->setActive(false);
        $this->assertFalse($this->user->isActive());
    }

    public function testSetGetEmailVerified(): void
    {
        $this->user->setEmailVerified(true);
        $this->assertTrue($this->user->isEmailVerified());
    }

    public function testSetGetFailedAttempts(): void
    {
        $this->user->setFailedAttempts(3);
        $this->assertEquals(3, $this->user->getFailedAttempts());
    }

    public function testSetGetResetToken(): void
    {
        $this->user->setResetToken('abc123');
        $this->assertEquals('abc123', $this->user->getResetToken());
    }

    public function testSetGetResetTokenNull(): void
    {
        $this->user->setResetToken(null);
        $this->assertNull($this->user->getResetToken());
    }

    public function testSetGetResetTokenExpiresAt(): void
    {
        $date = new \DateTime('+1 hour');
        $this->user->setResetTokenExpiresAt($date);
        $this->assertSame($date, $this->user->getResetTokenExpiresAt());
    }

    public function testSetGetVerificationToken(): void
    {
        $this->user->setVerificationToken('verify_xyz');
        $this->assertEquals('verify_xyz', $this->user->getVerificationToken());
    }

    public function testSetGetAuthProvider(): void
    {
        $this->user->setAuthProvider('GOOGLE');
        $this->assertEquals('GOOGLE', $this->user->getAuthProvider());
    }

    public function testSetGetProviderId(): void
    {
        $this->user->setProviderId('google_123');
        $this->assertEquals('google_123', $this->user->getProviderId());
    }

    public function testSetGetPointsBalance(): void
    {
        $this->user->setPointsBalance(150);
        $this->assertEquals(150, $this->user->getPointsBalance());
    }

    public function testSetGetCreatedAt(): void
    {
        $date = new \DateTime();
        $this->user->setCreatedAt($date);
        $this->assertSame($date, $this->user->getCreatedAt());
    }

    // ── Profile relation ──
    public function testProfileIsNullByDefault(): void
    {
        $this->assertNull($this->user->getProfile());
    }

    public function testSetGetProfile(): void
    {
        $profile = new Profile();
        $this->user->setProfile($profile);
        $this->assertSame($profile, $this->user->getProfile());
    }

    // ── Symfony Security ──
    public function testGetRolesReturnsArray(): void
    {
        $this->user->setRole('RECRUITER');
        $roles = $this->user->getRoles();
        $this->assertIsArray($roles);
        $this->assertContains('ROLE_RECRUITER', $roles);
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $this->user->setEmail('user@test.com');
        $this->assertEquals('user@test.com', $this->user->getUserIdentifier());
    }

    // ── Edge cases ──
    public function testNegativePointsBalance(): void
    {
        $this->user->setPointsBalance(-10);
        $this->assertEquals(-10, $this->user->getPointsBalance());
    }

    public function testHighFailedAttempts(): void
    {
        $this->user->setFailedAttempts(999);
        $this->assertEquals(999, $this->user->getFailedAttempts());
    }
}
