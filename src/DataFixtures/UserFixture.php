<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixture extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = User::create('test@test.com');

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            'test123'
        );
        $user->setPasswordHash($hashedPassword);

        $manager->persist($user);
        $manager->flush();
    }
}
