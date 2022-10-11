<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;


class SuperAdminFixtures extends Fixture implements FixtureGroupInterface
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    // Création d'un utilisateur SuperAdmin'
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setEmail('admin@bodycool.fr');
        $user->setActive(true);
        $user->setPhone('0674958563');
        $user->setActivationToken(null);

        $password = $this->hasher->hashPassword($user, '123456789');
        $user->setPassword($password);


        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $user->setAvatar('avatar-super-admin.jpg');

        $manager->persist($user);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['groupSuperAdmin'];
    }


}
