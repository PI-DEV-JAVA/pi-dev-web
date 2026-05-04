<?php

namespace App\Tests\Entity;

use App\Entity\Profile;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ProfileTest extends TestCase
{
    private Profile $profile;

    protected function setUp(): void
    {
        $this->profile = new Profile();
    }

    // ── Defaults ──
    public function testDefaultProfileNotCompleted(): void
    {
        $this->assertFalse($this->profile->isProfileCompleted());
    }

    public function testDefaultSkillsEmpty(): void
    {
        $this->assertIsArray($this->profile->getSkills());
        $this->assertEmpty($this->profile->getSkills());
    }

    // ── Name fields ──
    public function testSetGetFirstName(): void
    {
        $this->profile->setFirstName('Ayoub');
        $this->assertEquals('Ayoub', $this->profile->getFirstName());
    }

    public function testSetGetLastName(): void
    {
        $this->profile->setLastName('Ben Ali');
        $this->assertEquals('Ben Ali', $this->profile->getLastName());
    }

    public function testFullNameConcatenation(): void
    {
        $this->profile->setFirstName('Ayoub');
        $this->profile->setLastName('Ben Ali');
        $this->assertStringContainsString('Ayoub', $this->profile->getFullName());
        $this->assertStringContainsString('Ben Ali', $this->profile->getFullName());
    }

    // ── Contact info ──
    public function testSetGetPhoneNumber(): void
    {
        $this->profile->setPhoneNumber('+216 55 123 456');
        $this->assertEquals('+216 55 123 456', $this->profile->getPhoneNumber());
    }

    public function testSetGetLocation(): void
    {
        $this->profile->setLocation('Tunis, Tunisie');
        $this->assertEquals('Tunis, Tunisie', $this->profile->getLocation());
    }

    // ── Professional info ──
    public function testSetGetProfessionalTitle(): void
    {
        $this->profile->setProfessionalTitle('Développeur Full Stack');
        $this->assertEquals('Développeur Full Stack', $this->profile->getProfessionalTitle());
    }

    public function testSetGetYearsOfExperience(): void
    {
        $this->profile->setYearsOfExperience(5);
        $this->assertEquals(5, $this->profile->getYearsOfExperience());
    }

    public function testSetGetSummary(): void
    {
        $this->profile->setSummary('Développeur passionné avec 5 ans.');
        $this->assertEquals('Développeur passionné avec 5 ans.', $this->profile->getSummary());
    }

    // ── Files ──
    public function testSetGetCvPath(): void
    {
        $this->profile->setCvPath('/uploads/cvs/my-cv.pdf');
        $this->assertEquals('/uploads/cvs/my-cv.pdf', $this->profile->getCvPath());
    }

    public function testSetGetProfilePicturePath(): void
    {
        $this->profile->setProfilePicturePath('/uploads/avatars/pic.jpg');
        $this->assertEquals('/uploads/avatars/pic.jpg', $this->profile->getProfilePicturePath());
    }

    // ── Skills ──
    public function testSetGetSkills(): void
    {
        $skills = ['PHP', 'Symfony', 'JavaScript'];
        $this->profile->setSkills($skills);
        $this->assertEquals($skills, $this->profile->getSkills());
        $this->assertCount(3, $this->profile->getSkills());
    }

    public function testEmptySkills(): void
    {
        $this->profile->setSkills([]);
        $this->assertEmpty($this->profile->getSkills());
    }

    // ── Profile completed ──
    public function testSetProfileCompleted(): void
    {
        $this->profile->setProfileCompleted(true);
        $this->assertTrue($this->profile->isProfileCompleted());
    }

    // ── Birth date ──
    public function testSetGetBirthDate(): void
    {
        $date = new \DateTime('1998-03-15');
        $this->profile->setBirthDate($date);
        $this->assertSame($date, $this->profile->getBirthDate());
    }

    // ── User relation ──
    public function testSetGetUser(): void
    {
        $user = new User();
        $this->profile->setUser($user);
        $this->assertSame($user, $this->profile->getUser());
    }

    // ── Null handling ──
    public function testNullCvPath(): void
    {
        $this->assertNull($this->profile->getCvPath());
    }

    public function testNullYearsExperience(): void
    {
        $this->assertNull($this->profile->getYearsOfExperience());
    }

    public function testZeroYearsExperience(): void
    {
        $this->profile->setYearsOfExperience(0);
        $this->assertEquals(0, $this->profile->getYearsOfExperience());
    }
}
