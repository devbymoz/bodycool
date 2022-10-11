<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;


class UsersFixtures extends Fixture implements FixtureGroupInterface
{
    private User $user;
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    // Création de faux utilisateurs
    public function load(ObjectManager $manager): void
    {
        // Création d'ADMIN
        for ($i = 0; $i < 5; $i++) {
            $user = $this->createUser();
            $user->setRoles(['ROLE_ADMIN']);
            $manager->persist($user);
        }

        // Création de GESTIONNAIRE
        for ($i = 0; $i < 26; $i++) {
            $user = $this->createUser();
            $user->setRoles(['ROLE_GESTIONNAIRE']);
            $manager->persist($user);
        }

        $manager->flush();
    }


    public function createUser(): User
    {
        $faker = Faker\Factory::create('fr_FR');

        // Création de nouvel utilisateur
        $this->user = new User();
        $this->user->setFirstname($faker->firstName());
        $this->user->setLastname($faker->lastName());
        $this->user->setEmail($faker->unique()->companyEmail());
        $this->user->setActive(true);
        $this->user->setPhone('09' . $faker->randomNumber(8, true));
        $this->user->setActivationToken(null);

        $password = $this->hasher->hashPassword($this->user, '123456789');
        $this->user->setPassword($password);

        return $this->user;
    }

    
    public static function getGroups(): array
    {
        return ['user'];
    }

}
