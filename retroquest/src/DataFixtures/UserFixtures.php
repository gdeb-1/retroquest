<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const COLLECTOR_REFERENCE_PREFIX = 'collector_';
    public const MODERATOR_REFERENCE_PREFIX = 'moderator_';
    public const ADMIN_REFERENCE_PREFIX = 'admin_';

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $defaultPassword = 'RetroPassword123!';

        // 1. Create 10 Collectors
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail(sprintf('collector%d@example.com', $i + 1));
            $user->setRoles(['ROLE_COLLECTOR']);
            
            $hashedPassword = $this->passwordHasher->hashPassword($user, $defaultPassword);
            $user->setPassword($hashedPassword);
            
            $manager->persist($user);
            
            $this->addReference(self::COLLECTOR_REFERENCE_PREFIX . $i, $user);
        }

        // 2. Create 2 Moderators
        for ($i = 0; $i < 2; $i++) {
            $user = new User();
            $user->setEmail(sprintf('moderator%d@example.com', $i + 1));
            $user->setRoles(['ROLE_MODERATOR', 'ROLE_COLLECTOR']);
            
            $hashedPassword = $this->passwordHasher->hashPassword($user, $defaultPassword);
            $user->setPassword($hashedPassword);
            
            $manager->persist($user);
            
            $this->addReference(self::MODERATOR_REFERENCE_PREFIX . $i, $user);
        }

        // 3. Create 1 Admin
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_MODERATOR', 'ROLE_COLLECTOR']);
        
        $hashedPassword = $this->passwordHasher->hashPassword($admin, $defaultPassword);
        $admin->setPassword($hashedPassword);
        
        $manager->persist($admin);
        
        $this->addReference(self::ADMIN_REFERENCE_PREFIX . '0', $admin);

        $manager->flush();
    }
}
