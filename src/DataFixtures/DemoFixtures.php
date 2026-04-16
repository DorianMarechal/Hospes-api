<?php

namespace App\DataFixtures;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DemoFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@hospes.dev');
        $admin->setFirstName('Admin');
        $admin->setLastName('Hospes');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin@Hospes2026'));
        $admin->setIsActive(true);
        $admin->setCreatedAt(new DateTimeImmutable());
        $admin->setUpdatedAt(new DateTimeImmutable());
        $manager->persist($admin);

        $host = new User();
        $host->setEmail('host@hospes.dev');
        $host->setFirstName('Demo');
        $host->setLastName('Host');
        $host->setRoles(['ROLE_HOST']);
        $host->setPassword($this->passwordHasher->hashPassword($host, 'Host@Hospes2026'));
        $host->setIsActive(true);
        $host->setCreatedAt(new DateTimeImmutable());
        $host->setUpdatedAt(new DateTimeImmutable());
        $manager->persist($host);

        $manager->flush();
    }
}
