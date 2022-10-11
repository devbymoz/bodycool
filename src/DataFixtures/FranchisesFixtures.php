<?php

namespace App\DataFixtures;

use App\Entity\Franchise;
use App\Entity\Permission;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;


class FranchisesFixtures extends Fixture implements FixtureGroupInterface
{
    private $doctrine;
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher, ManagerRegistry $doctrine)
    {
        $this->hasher = $hasher;
        $this->doctrine = $doctrine;
    }

    // Création de 93 fausses franchises OK
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 0; $i < 93; $i++) {
            $franchise = new Franchise();
            $franchise->setName($faker->unique()->company());

            $slugger = new AsciiSlugger();
            $slug = $slugger->slug(strtolower($franchise->getName()));
            $franchise->setSlug($slug);

            // Ajout du propriétaire
            $user = new User();
            $user->setFirstname($faker->firstName());
            $user->setLastname($faker->lastName());
            $user->setEmail($faker->unique()->companyEmail());
            $user->setActive(true);
            $user->setPhone('09' . $faker->randomNumber(8, true));
            $user->setActivationToken(null);
            $user->setRoles(['ROLE_FRANCHISE']);
            $password = $this->hasher->hashPassword($user, '123456789');
            $user->setPassword($password);

            $franchise->setUserOwner($user);

            // Ajout de fonctionnalités globales à la franchise
            foreach ($this->permissions() as $permission) {
                $franchise->addGlobalPermission($permission);
            }

            $manager->persist($franchise);
            $this->addReference('franchise'. $i, $franchise);
        }
        $manager->flush();
    }


    // Récuperation de permissions aléatoirement.
    public function permissions(): array
    {
        $repo = $this->doctrine->getRepository(Permission::class);

        // On récupère les Permissions de la BDD
        $permissions = $repo->findAll();

        // On mélange le tableau
        shuffle($permissions);

        // On prend un nombre aléatoire.
        $rand =  rand(0, count($permissions));

        // On coupe le tableau avec le nombre aléatoire.
        $permissions = array_slice($permissions, $rand);

        return $permissions;
    }

    public static function getGroups(): array
    {
        return ['franchise'];
    }

}
