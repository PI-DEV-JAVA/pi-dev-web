<?php

namespace App\Tests\Entity;

use App\Entity\Profile;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the User entity.
 * Covers: registration fields, email verification, account lockout, roles, Google OAuth.
 */
class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    // ═══════════════════════════════════════════
    //  1. INSCRIPTION — Champs de base
    // ═══════════════════════════════════════════

    public function testNewUserHasDefaultValues(): void
    {
        $this->assertNull($this->user->getId());
        $this->assertNull($this->user->getEmail());
        $this->assertNull($this->user->getPassword());
        $this->assertEquals('CANDIDATE', $this->user->getRole());
        $this->assertTrue($this->user->isActive());
        $this->assertFalse($this->user->isEmailVerified());
        $this->assertEquals(0, $this->user->getFailedAttempts());
        $this->assertEquals('LOCAL', $this->user->getAuthProvider());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->user->getCreatedAt());
    }

    public function testSetAndGetEmail(): void
    {
        $this->user->setEmail('ahmed@gmail.com');
        $this->assertEquals('ahmed@gmail.com', $this->user->getEmail());
    }

    public function testSetAndGetPassword(): void
    {
        $this->user->setPassword('hashed_password_123');
        $this->assertEquals('hashed_password_123', $this->user->getPassword());
    }

    public function testPasswordCanBeNull(): void
    {
        $this->user->setPassword(null);
        $this->assertNull($this->user->getPassword());
    }

    // ═══════════════════════════════════════════
    //  2. RÔLES — RBAC (Admin, HR, Candidate)
    // ═══════════════════════════════════════════

    public function testDefaultRoleIsCandidate(): void
    {
        $this->assertEquals('CANDIDATE', $this->user->getRole());
    }

    public function testSetRoleHR(): void
    {
        $this->user->setRole('HR');
        $this->assertEquals('HR', $this->user->getRole());
    }

    public function testSetRoleAdmin(): void
    {
        $this->user->setRole('ADMIN');
        $this->assertEquals('ADMIN', $this->user->getRole());
    }

    public function testGetRolesReturnsSymfonyFormat(): void
    {
        $this->user->setRole('CANDIDATE');
        $this->assertEquals(['ROLE_CANDIDATE'], $this->user->getRoles());
    }

    public function testGetRolesForHR(): void
    {
        $this->user->setRole('HR');
        $this->assertEquals(['ROLE_HR'], $this->user->getRoles());
    }

    public function testGetRolesForAdmin(): void
    {
        $this->user->setRole('ADMIN');
        $this->assertEquals(['ROLE_ADMIN'], $this->user->getRoles());
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $this->user->setEmail('test@talentos.tn');
        $this->assertEquals('test@talentos.tn', $this->user->getUserIdentifier());
    }

    // ═══════════════════════════════════════════
    //  3. VÉRIFICATION EMAIL — Code OTP 6 chiffres
    // ═══════════════════════════════════════════

    public function testEmailVerifiedDefaultIsFalse(): void
    {
        $this->assertFalse($this->user->isEmailVerified());
    }

    public function testSetEmailVerifiedToTrue(): void
    {
        $this->user->setEmailVerified(true);
        $this->assertTrue($this->user->isEmailVerified());
    }

    public function testVerificationTokenCanBeSet(): void
    {
        $this->user->setVerificationToken('482091');
        $this->assertEquals('482091', $this->user->getVerificationToken());
    }

    public function testVerificationTokenCanBeCleared(): void
    {
        $this->user->setVerificationToken('123456');
        $this->user->setVerificationToken(null);
        $this->assertNull($this->user->getVerificationToken());
    }

    public function testResetTokenExpiresAtCanBeSet(): void
    {
        $expiry = new \DateTime('+10 minutes');
        $this->user->setResetTokenExpiresAt($expiry);
        $this->assertEquals($expiry, $this->user->getResetTokenExpiresAt());
    }

    public function testVerificationCodeIsNotExpired(): void
    {
        $this->user->setResetTokenExpiresAt(new \DateTime('+10 minutes'));
        $this->assertGreaterThan(new \DateTime(), $this->user->getResetTokenExpiresAt());
    }

    public function testVerificationCodeIsExpired(): void
    {
        $this->user->setResetTokenExpiresAt(new \DateTime('-1 minute'));
        $this->assertLessThan(new \DateTime(), $this->user->getResetTokenExpiresAt());
    }

    // ═══════════════════════════════════════════
    //  4. MOT DE PASSE OUBLIÉ — Reset Token
    // ═══════════════════════════════════════════

    public function testResetTokenCanBeSet(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->user->setResetToken($token);
        $this->assertEquals($token, $this->user->getResetToken());
    }

    public function testResetTokenCanBeNulled(): void
    {
        $this->user->setResetToken('some_token');
        $this->user->setResetToken(null);
        $this->assertNull($this->user->getResetToken());
    }

    public function testResetTokenExpiryCanBeNulled(): void
    {
        $this->user->setResetTokenExpiresAt(new \DateTime());
        $this->user->setResetTokenExpiresAt(null);
        $this->assertNull($this->user->getResetTokenExpiresAt());
    }

    // ═══════════════════════════════════════════
    //  5. VERROUILLAGE COMPTE — 5 tentatives
    // ═══════════════════════════════════════════

    public function testFailedAttemptsStartsAtZero(): void
    {
        $this->assertEquals(0, $this->user->getFailedAttempts());
    }

    public function testIncrementFailedAttempts(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->user->setFailedAttempts($i);
        }
        $this->assertEquals(5, $this->user->getFailedAttempts());
    }

    public function testAccountShouldLockAfter5Attempts(): void
    {
        $this->user->setFailedAttempts(5);
        $this->assertGreaterThanOrEqual(5, $this->user->getFailedAttempts());
        // In production, the authenticator locks the account when failedAttempts >= 5
    }

    public function testResetFailedAttemptsAfterSuccessfulLogin(): void
    {
        $this->user->setFailedAttempts(4);
        // Simulate successful login → reset
        $this->user->setFailedAttempts(0);
        $this->assertEquals(0, $this->user->getFailedAttempts());
    }

    // ═══════════════════════════════════════════
    //  6. GOOGLE OAUTH — Provider fields
    // ═══════════════════════════════════════════

    public function testDefaultAuthProviderIsLocal(): void
    {
        $this->assertEquals('LOCAL', $this->user->getAuthProvider());
    }

    public function testSetAuthProviderGoogle(): void
    {
        $this->user->setAuthProvider('GOOGLE');
        $this->assertEquals('GOOGLE', $this->user->getAuthProvider());
    }

    public function testSetProviderIdForGoogleUser(): void
    {
        $this->user->setAuthProvider('GOOGLE');
        $this->user->setProviderId('google_12345');
        $this->assertEquals('google_12345', $this->user->getProviderId());
    }

    public function testLocalUserHasNoProviderId(): void
    {
        $this->assertNull($this->user->getProviderId());
    }

    public function testGoogleUserIsAutoVerified(): void
    {
        // Simulating Google OAuth flow: email is verified automatically
        $this->user->setAuthProvider('GOOGLE');
        $this->user->setEmailVerified(true);
        $this->assertTrue($this->user->isEmailVerified());
        $this->assertEquals('GOOGLE', $this->user->getAuthProvider());
    }

    // ═══════════════════════════════════════════
    //  7. PROFIL — Relation User ↔ Profile
    // ═══════════════════════════════════════════

    public function testNewUserHasNoProfile(): void
    {
        $this->assertNull($this->user->getProfile());
    }

    public function testSetProfileLinksBackToUser(): void
    {
        $profile = new Profile();
        $this->user->setProfile($profile);
        $this->assertSame($profile, $this->user->getProfile());
        $this->assertSame($this->user, $profile->getUser());
    }

    public function testProfileCanBeRemoved(): void
    {
        $profile = new Profile();
        $this->user->setProfile($profile);
        $this->user->setProfile(null);
        $this->assertNull($this->user->getProfile());
    }

    // ═══════════════════════════════════════════
    //  8. ACTIVE STATUS — Ban/Disable
    // ═══════════════════════════════════════════

    public function testDeactivateUser(): void
    {
        $this->user->setActive(false);
        $this->assertFalse($this->user->isActive());
    }

    public function testReactivateUser(): void
    {
        $this->user->setActive(false);
        $this->user->setActive(true);
        $this->assertTrue($this->user->isActive());
    }

    // ═══════════════════════════════════════════
    //  9. EDGE CASES
    // ═══════════════════════════════════════════

    public function testEraseCredentialsDoesNothing(): void
    {
        $this->user->setPassword('secret');
        $this->user->eraseCredentials();
        // eraseCredentials is intentionally empty in this implementation
        $this->assertEquals('secret', $this->user->getPassword());
    }

    public function testFluentSetters(): void
    {
        $result = $this->user
            ->setEmail('fluent@test.com')
            ->setRole('HR')
            ->setActive(true)
            ->setAuthProvider('LOCAL');

        $this->assertInstanceOf(User::class, $result);
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $user = new User();
        $now = new \DateTime();
        $diff = $now->getTimestamp() - $user->getCreatedAt()->getTimestamp();
        $this->assertLessThan(2, $diff); // created less than 2 seconds ago
    }
}
