<?php

namespace App\Tests\Entity;

use App\Entity\Profile;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Profile entity.
 * Covers: profile completion, personal info, CV upload, user relationship.
 */
class ProfileTest extends TestCase
{
    private Profile $profile;

    protected function setUp(): void
    {
        $this->profile = new Profile();
    }

    // ═══════════════════════════════════════════
    //  1. PROFIL — Valeurs par défaut
    // ═══════════════════════════════════════════

    public function testNewProfileHasDefaultValues(): void
    {
        $this->assertNull($this->profile->getId());
        $this->assertNull($this->profile->getFirstName());
        $this->assertNull($this->profile->getLastName());
        $this->assertFalse($this->profile->isProfileCompleted());
        $this->assertNull($this->profile->getCvPath());
        $this->assertNull($this->profile->getProfilePicturePath());
    }

    // ═══════════════════════════════════════════
    //  2. INFORMATIONS PERSONNELLES
    // ═══════════════════════════════════════════

    public function testSetAndGetFirstName(): void
    {
        $this->profile->setFirstName('Ahmed');
        $this->assertEquals('Ahmed', $this->profile->getFirstName());
    }

    public function testSetAndGetLastName(): void
    {
        $this->profile->setLastName('Ben Ali');
        $this->assertEquals('Ben Ali', $this->profile->getLastName());
    }

    public function testGetFullNameWithBothNames(): void
    {
        $this->profile->setFirstName('Ahmed');
        $this->profile->setLastName('Ben Ali');
        $this->assertEquals('Ahmed Ben Ali', $this->profile->getFullName());
    }

    public function testGetFullNameWithOnlyFirstName(): void
    {
        $this->profile->setFirstName('Ahmed');
        $this->assertEquals('Ahmed', $this->profile->getFullName());
    }

    public function testGetFullNameWithNoNames(): void
    {
        $this->assertEquals('', $this->profile->getFullName());
    }

    public function testSetAndGetPhoneNumber(): void
    {
        $this->profile->setPhoneNumber('+216 55 123 456');
        $this->assertEquals('+216 55 123 456', $this->profile->getPhoneNumber());
    }

    public function testSetAndGetBirthDate(): void
    {
        $date = new \DateTime('1998-05-15');
        $this->profile->setBirthDate($date);
        $this->assertEquals($date, $this->profile->getBirthDate());
    }

    public function testSetAndGetLocation(): void
    {
        $this->profile->setLocation('Tunis, Tunisie');
        $this->assertEquals('Tunis, Tunisie', $this->profile->getLocation());
    }

    // ═══════════════════════════════════════════
    //  3. INFORMATIONS PROFESSIONNELLES
    // ═══════════════════════════════════════════

    public function testSetProfessionalTitle(): void
    {
        $this->profile->setProfessionalTitle('Développeur Full Stack');
        $this->assertEquals('Développeur Full Stack', $this->profile->getProfessionalTitle());
    }

    public function testSetYearsOfExperience(): void
    {
        $this->profile->setYearsOfExperience(3);
        $this->assertEquals(3, $this->profile->getYearsOfExperience());
    }

    public function testSetSummary(): void
    {
        $summary = 'Développeur passionné avec 3 ans d\'expérience en Symfony et React.';
        $this->profile->setSummary($summary);
        $this->assertStringContainsString('Symfony', $this->profile->getSummary());
    }

    // ═══════════════════════════════════════════
    //  4. COMPLÉTION DE PROFIL — Redirection login
    // ═══════════════════════════════════════════

    public function testProfileNotCompletedByDefault(): void
    {
        $this->assertFalse($this->profile->isProfileCompleted());
    }

    public function testMarkProfileAsCompleted(): void
    {
        $this->profile->setProfileCompleted(true);
        $this->assertTrue($this->profile->isProfileCompleted());
    }

    public function testIncompleteProfileRedirectScenario(): void
    {
        // Simulates: user logged in but profile not completed → should redirect
        $user = new User();
        $user->setEmail('new_user@test.com');
        $this->profile->setUser($user);
        $this->profile->setProfileCompleted(false);

        $this->assertFalse($this->profile->isProfileCompleted());
        // In the app, SecurityController redirects to /completeprofile
    }

    public function testCompletedProfileAllowsDashboard(): void
    {
        $this->profile->setFirstName('Ahmed');
        $this->profile->setLastName('Ben Ali');
        $this->profile->setProfessionalTitle('Dev PHP');
        $this->profile->setYearsOfExperience(2);
        $this->profile->setProfileCompleted(true);

        $this->assertTrue($this->profile->isProfileCompleted());
        $this->assertNotEmpty($this->profile->getFullName());
    }

    // ═══════════════════════════════════════════
    //  5. FICHIERS — Photo de profil & CV
    // ═══════════════════════════════════════════

    public function testSetProfilePicturePath(): void
    {
        $this->profile->setProfilePicturePath('/uploads/profiles/ahmed.jpg');
        $this->assertEquals('/uploads/profiles/ahmed.jpg', $this->profile->getProfilePicturePath());
    }

    public function testSetCvPath(): void
    {
        $this->profile->setCvPath('/uploads/cv/ahmed_cv.pdf');
        $this->assertEquals('/uploads/cv/ahmed_cv.pdf', $this->profile->getCvPath());
    }

    public function testProfilePictureCanBeNull(): void
    {
        $this->profile->setProfilePicturePath('/uploads/old.jpg');
        $this->profile->setProfilePicturePath(null);
        $this->assertNull($this->profile->getProfilePicturePath());
    }

    // ═══════════════════════════════════════════
    //  6. RELATION User ↔ Profile
    // ═══════════════════════════════════════════

    public function testSetUserOnProfile(): void
    {
        $user = new User();
        $user->setEmail('profile_test@test.com');
        $this->profile->setUser($user);
        $this->assertSame($user, $this->profile->getUser());
    }

    public function testBidirectionalRelationship(): void
    {
        $user = new User();
        $user->setEmail('bidir@test.com');
        $user->setProfile($this->profile);

        $this->assertSame($this->profile, $user->getProfile());
        $this->assertSame($user, $this->profile->getUser());
    }

    // ═══════════════════════════════════════════
    //  7. FLUENT API
    // ═══════════════════════════════════════════

    public function testFluentSetters(): void
    {
        $result = $this->profile
            ->setFirstName('Test')
            ->setLastName('User')
            ->setLocation('Sfax')
            ->setProfessionalTitle('Designer')
            ->setYearsOfExperience(1)
            ->setProfileCompleted(true);

        $this->assertInstanceOf(Profile::class, $result);
        $this->assertTrue($result->isProfileCompleted());
    }
}
