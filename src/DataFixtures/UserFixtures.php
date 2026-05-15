<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{

    private UserPasswordHasherInterface $passwordHasher;

    // 1. On injecte le service ici
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();

        $user->setEmail('test@gmail.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setApiAccess(false);

        $manager->persist($user);

        $manager->flush();
    }
}
