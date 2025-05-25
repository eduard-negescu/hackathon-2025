<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use Webmozart\Assert\Assert;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function register(string $username, string $password): User
    {
        // Validate username and password
        error_log("Validating user credentials: $username\n");
        Assert::notEmpty($username, 'Username cannot be empty.');
        Assert::notEmpty($password, 'Password cannot be empty.');
        Assert::minLength($username, 4, 'Username must be at least 4 characters long.');
        Assert::minLength($password, 8, 'Password must be at least 8 characters long.');
        Assert::regex($password, '/\d/', 'Password must contain at least one digit');

        // Check if the username is taken
        $existingUser = $this->users->findByUsername($username);
        Assert::null($existingUser, 'Username is already taken.');

        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        Assert::notFalse($passwordHash, 'Failed to hash the password.');

        error_log("Passed validation for user: $username\n");

        // create and save the user
        $user = new User(
            null, 
            $username, 
            $passwordHash, 
            new \DateTimeImmutable()
        );
        $this->users->save($user);

        return $user;
    }

    public function attempt(string $username, string $password): bool
    {
        // check user and password
        $user = $this->users->findByUsername($username);
        Assert::notNull($user, 'User not found');
        Assert::notFalse(password_verify($password, $user->passwordHash), 'Wrong password');
        
        $_SESSION['user_id'] = $user->id; // Store user ID in session
        $_SESSION['username'] = $user->username; // Store username in session

        return true;
    }
}
