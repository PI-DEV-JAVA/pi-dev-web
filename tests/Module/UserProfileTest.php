<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  MODULE: User & Profile
 *  Covers: User entity, Profile entity, ProfileController logic
 *  Tests: defaults, getters/setters, role system, auth,
 *         profile completeness, password reset flow, skills
 * ═══════════════════════════════════════════════════════════════
 */

namespace App\Tests\Module;

use App\Entity\User;
use App\Entity\Profile;
use PHPUnit\Framework\TestCase;

class UserProfileTest extends TestCase
{
    // ┌─────────────────────────────────────┐
    // │  USER ENTITY — Success Scenarios    │
    // └─────────────────────────────────────┘

    public function testUserCreationWithDefaults(): void
    {
        $user = new User();
        $this->assertNull($user->getId());
        $this->assertEquals('CANDIDATE', $user->getRole());
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isEmailVerified());
        $this->assertEquals(0, $user->getFailedAttempts());
        $this->assertEquals(0, $user->getPointsBalance());
        $this->assertEquals('LOCAL', $user->getAuthProvider());
        $this->assertNull($user->getProfile());
    }

    public function testSetEmailSuccessfully(): void
    {
        $user = new User();
        $user->setEmail('ayoub@talentos.tn');
        $this->assertEquals('ayoub@talentos.tn', $user->getEmail());
    }

    public function testSetPasswordSuccessfully(): void
    {
        $user = new User();
        $user->setPassword('$2y$13$hashedPasswordHere');
        $this->assertEquals('$2y$13$hashedPasswordHere', $user->getPassword());
    }

    public function testSetRoleRecruiter(): void
    {
        $user = new User();
        $user->setRole('RECRUITER');
        $this->assertEquals('RECRUITER', $user->getRole());
    }

    public function testSetRoleAdmin(): void
    {
        $user = new User();
        $user->setRole('ADMIN');
        $this->assertEquals('ADMIN', $user->getRole());
    }

    public function testGetRolesReturnsSymfonyFormat(): void
    {
        $user = new User();
        $user->setRole('RECRUITER');
        $roles = $user->getRoles();
        $this->assertIsArray($roles);
        $this->assertContains('ROLE_RECRUITER', $roles);
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = new User();
        $user->setEmail('test@test.com');
        $this->assertEquals('test@test.com', $user->getUserIdentifier());
    }

    public function testActivateDeactivateUser(): void
    {
        $user = new User();
        $this->assertTrue($user->isActive());
        $user->setActive(false);
        $this->assertFalse($user->isActive());
        $user->setActive(true);
        $this->assertTrue($user->isActive());
    }

    public function testEmailVerificationFlow(): void
    {
        $user = new User();
        $this->assertFalse($user->isEmailVerified());

        // Set verification token
        $user->setVerificationToken('token_abc123');
        $this->assertEquals('token_abc123', $user->getVerificationToken());

        // Verify email
        $user->setEmailVerified(true);
        $this->assertTrue($user->isEmailVerified());

        // Clear token after verification
        $user->setVerificationToken(null);
        $this->assertNull($user->getVerificationToken());
    }

    public function testPasswordResetFlow(): void
    {
        $user = new User();

        // Generate reset token with expiry
        $token = bin2hex(random_bytes(16));
        $expiry = new \DateTime('+1 hour');

        $user->setResetToken($token);
        $user->setResetTokenExpiresAt($expiry);

        $this->assertEquals($token, $user->getResetToken());
        $this->assertSame($expiry, $user->getResetTokenExpiresAt());

        // Token should not be expired
        $this->assertGreaterThan(new \DateTime(), $user->getResetTokenExpiresAt());

        // Clear after use
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $this->assertNull($user->getResetToken());
    }

    public function testFailedLoginAttemptsIncrement(): void
    {
        $user = new User();
        $this->assertEquals(0, $user->getFailedAttempts());

        $user->setFailedAttempts(1);
        $this->assertEquals(1, $user->getFailedAttempts());

        $user->setFailedAttempts(3);
        $this->assertEquals(3, $user->getFailedAttempts());

        // Reset after successful login
        $user->setFailedAttempts(0);
        $this->assertEquals(0, $user->getFailedAttempts());
    }

    public function testGoogleOAuthProvider(): void
    {
        $user = new User();
        $user->setAuthProvider('GOOGLE');
        $user->setProviderId('google_uid_123456');
        $this->assertEquals('GOOGLE', $user->getAuthProvider());
        $this->assertEquals('google_uid_123456', $user->getProviderId());
    }

    public function testPointsBalanceOperations(): void
    {
        $user = new User();
        $this->assertEquals(0, $user->getPointsBalance());

        // Earn points
        $user->setPointsBalance(100);
        $this->assertEquals(100, $user->getPointsBalance());

        // Spend points
        $user->setPointsBalance($user->getPointsBalance() - 30);
        $this->assertEquals(70, $user->getPointsBalance());
    }

    public function testCreatedAtTimestamp(): void
    {
        $user = new User();
        $now = new \DateTime();
        $user->setCreatedAt($now);
        $this->assertSame($now, $user->getCreatedAt());
    }

    // ┌─────────────────────────────────────┐
    // │  USER ENTITY — Failure Scenarios    │
    // └─────────────────────────────────────┘

    public function testFailNullEmailReturnsNull(): void
    {
        $user = new User();
        $this->assertNull($user->getEmail());
    }

    public function testFailNullPasswordReturnsNull(): void
    {
        $user = new User();
        $this->assertNull($user->getPassword());
    }

    public function testFailNegativePointsStillAccepted(): void
    {
        // Edge case — system should handle this at controller level
        $user = new User();
        $user->setPointsBalance(-50);
        $this->assertEquals(-50, $user->getPointsBalance());
    }

    public function testFailExpiredResetToken(): void
    {
        $user = new User();
        $user->setResetToken('expired_token');
        $user->setResetTokenExpiresAt(new \DateTime('-1 hour'));

        // Token is set but expired
        $this->assertNotNull($user->getResetToken());
        $this->assertLessThan(new \DateTime(), $user->getResetTokenExpiresAt());
    }

    public function testFailUserWithoutProfile(): void
    {
        $user = new User();
        $this->assertNull($user->getProfile());
    }

    // ┌──────────────────────────────────────┐
    // │  PROFILE ENTITY — Success Scenarios  │
    // └──────────────────────────────────────┘

    public function testProfileCreationWithDefaults(): void
    {
        $profile = new Profile();
        $this->assertNull($profile->getId());
        $this->assertFalse($profile->isProfileCompleted());
        $this->assertIsArray($profile->getSkills());
        $this->assertEmpty($profile->getSkills());
        $this->assertNull($profile->getCvPath());
        $this->assertNull($profile->getProfilePicturePath());
    }

    public function testProfileSetFullName(): void
    {
        $profile = new Profile();
        $profile->setFirstName('Ayoub');
        $profile->setLastName('Ben Salah');
        $this->assertEquals('Ayoub', $profile->getFirstName());
        $this->assertEquals('Ben Salah', $profile->getLastName());
        $this->assertStringContainsString('Ayoub', $profile->getFullName());
    }

    public function testProfileSetContactInfo(): void
    {
        $profile = new Profile();
        $profile->setPhoneNumber('+216 55 123 456');
        $profile->setLocation('Tunis, Tunisie');
        $this->assertEquals('+216 55 123 456', $profile->getPhoneNumber());
        $this->assertEquals('Tunis, Tunisie', $profile->getLocation());
    }

    public function testProfileSetProfessionalInfo(): void
    {
        $profile = new Profile();
        $profile->setProfessionalTitle('Ingénieur Full Stack');
        $profile->setYearsOfExperience(5);
        $profile->setSummary('Développeur passionné spécialisé en Symfony');
        $this->assertEquals('Ingénieur Full Stack', $profile->getProfessionalTitle());
        $this->assertEquals(5, $profile->getYearsOfExperience());
        $this->assertStringContainsString('Symfony', $profile->getSummary());
    }

    public function testProfileSetSkillsArray(): void
    {
        $profile = new Profile();
        $skills = ['PHP', 'Symfony', 'JavaScript', 'React', 'Docker'];
        $profile->setSkills($skills);
        $this->assertCount(5, $profile->getSkills());
        $this->assertContains('Symfony', $profile->getSkills());
        $this->assertContains('Docker', $profile->getSkills());
    }

    public function testProfileSetCvPath(): void
    {
        $profile = new Profile();
        $profile->setCvPath('/uploads/cvs/cv-ayoub-2025.pdf');
        $this->assertEquals('/uploads/cvs/cv-ayoub-2025.pdf', $profile->getCvPath());
    }

    public function testProfileSetProfilePicture(): void
    {
        $profile = new Profile();
        $profile->setProfilePicturePath('/uploads/avatars/ayoub.jpg');
        $this->assertEquals('/uploads/avatars/ayoub.jpg', $profile->getProfilePicturePath());
    }

    public function testProfileSetBirthDate(): void
    {
        $profile = new Profile();
        $dob = new \DateTime('1998-03-15');
        $profile->setBirthDate($dob);
        $this->assertEquals('1998-03-15', $profile->getBirthDate()->format('Y-m-d'));
    }

    public function testProfileCompletedFlag(): void
    {
        $profile = new Profile();
        $this->assertFalse($profile->isProfileCompleted());
        $profile->setProfileCompleted(true);
        $this->assertTrue($profile->isProfileCompleted());
    }

    // ── Profile completeness simulation ──
    public function testProfileCompletenessCheck(): void
    {
        $profile = new Profile();
        $profile->setFirstName('Ayoub');
        $profile->setLastName('Ben Ali');
        $profile->setProfessionalTitle('Dev');
        $profile->setYearsOfExperience(3);
        $profile->setPhoneNumber('+216 55 123 456');
        $profile->setLocation('Tunis');

        // All required fields filled
        $isComplete = $profile->getFirstName() && $profile->getLastName()
            && $profile->getProfessionalTitle() && $profile->getPhoneNumber();
        $this->assertTrue($isComplete);
        $profile->setProfileCompleted(true);
        $this->assertTrue($profile->isProfileCompleted());
    }

    // ── User-Profile relation ──
    public function testUserProfileBidirectionalRelation(): void
    {
        $user = new User();
        $user->setEmail('ayoub@test.tn');
        $profile = new Profile();
        $profile->setFirstName('Ayoub');

        $user->setProfile($profile);
        $profile->setUser($user);

        $this->assertSame($profile, $user->getProfile());
        $this->assertSame($user, $profile->getUser());
        $this->assertEquals('ayoub@test.tn', $profile->getUser()->getEmail());
    }

    // ┌──────────────────────────────────────┐
    // │  PROFILE ENTITY — Failure Scenarios  │
    // └──────────────────────────────────────┘

    public function testFailProfileIncomplete(): void
    {
        $profile = new Profile();
        // Only partial data
        $profile->setFirstName('Ayoub');
        // Missing: lastName, phone, title
        $isComplete = $profile->getFirstName() && $profile->getLastName()
            && $profile->getProfessionalTitle() && $profile->getPhoneNumber();
        $this->assertFalse($isComplete);
    }

    public function testFailProfileNullExperience(): void
    {
        $profile = new Profile();
        $this->assertNull($profile->getYearsOfExperience());
    }

    public function testFailProfileEmptySkills(): void
    {
        $profile = new Profile();
        $profile->setSkills([]);
        $this->assertEmpty($profile->getSkills());
    }

    public function testFailProfileNoCv(): void
    {
        $profile = new Profile();
        $this->assertNull($profile->getCvPath());
    }

    public function testFailProfileZeroExperience(): void
    {
        $profile = new Profile();
        $profile->setYearsOfExperience(0);
        $this->assertEquals(0, $profile->getYearsOfExperience());
    }

    // ── ProfileController: searchSkills simulation ──
    public function testSearchSkillsFiltering(): void
    {
        $allSkills = ['PHP', 'Python', 'JavaScript', 'Java', 'Photoshop'];
        $query = 'ph';
        $results = array_filter($allSkills, fn($s) => stripos($s, $query) !== false);
        $this->assertCount(2, $results); // PHP + Photoshop
        $this->assertContains('PHP', $results);
    }

    public function testSearchSkillsEmptyQuery(): void
    {
        $allSkills = ['PHP', 'Python'];
        $query = '';
        $results = $query === '' ? [] : array_filter($allSkills, fn($s) => stripos($s, $query) !== false);
        $this->assertEmpty($results);
    }
}
