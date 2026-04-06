<?php

namespace App\Security;

use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Supports legacy SHA-256 hex hashes from the JavaFX app (PasswordUtil.java).
 * New passwords are hashed with SHA-256 as well for compatibility with the JavaFX app.
 * If you want to migrate to bcrypt later, change the hash() method.
 */
class LegacySha256PasswordHasher implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    public function hash(string $plainPassword): string
    {
        if ($this->isPasswordTooLong($plainPassword)) {
            throw new \InvalidArgumentException('The password is too long.');
        }

        // Match JavaFX PasswordUtil: SHA-256, no salt, hex encoding
        return hash('sha256', $plainPassword);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        if ($this->isPasswordTooLong($plainPassword)) {
            return false;
        }

        // Check if it's a legacy SHA-256 hash (64 hex chars, no $ prefix)
        if (strlen($hashedPassword) === 64 && ctype_xdigit($hashedPassword)) {
            return hash_equals($hashedPassword, hash('sha256', $plainPassword));
        }

        // Otherwise, fall back to password_verify for bcrypt/argon2 hashes
        return password_verify($plainPassword, $hashedPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        // Don't force rehash — keep SHA-256 for JavaFX compatibility
        return false;
    }
}
