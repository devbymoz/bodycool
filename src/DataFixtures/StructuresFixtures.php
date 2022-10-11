<?php

namespace App\DataFixtures;

use App\Entity\Structure;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use App\DataFixtures\FranchisesFixtures;
use Symfony\Component\String\Slugger\AsciiSlugger;
use App\Entity\Permission;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;


class StructuresFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private $doctrine;
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher, ManagerRegistry $doctrine)
    {
        $this->hasher = $hasher;
        $this->doctrine = $doctrine;
    }

    // Création de 153 nouvelles structures
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 0; $i < 153; $i++) {
            $structure = new Structure();
            $structure->setName($faker->unique()->company());
            $structure->setAddress($faker->address());
            $structure->setPhone('09' . $faker->randomNumber(8, true));
            $structure->setContractNumber('B' . $faker->unique()->randomNumber(4, true));
            $structure->setActive(rand(0, 1));

            $slugger = new AsciiSlugger();
            $slug = $slugger->slug(strtolower($structure->getName()));
            $structure->setSlug($slug);

            // On attribue une franchise aux structures.
            $rand = rand(0, 92);
            $structure->setFranchise($this->getReference('franchise' . $rand));

            $user = new User();
            $user->setFirstname($faker->firstName());
            $user->setLastname($faker->lastName());
            $user->setEmail($faker->unique()->companyEmail());
            $user->setActive(true);
            $user->setPhone('09' . $faker->randomNumber(8, true));
            $user->setActivationToken(null);
            $user->setRoles(['ROLE_GESTIONNAIRE']);
            $password = $this->hasher->hashPassword($user, '123456789');
            $user->setPassword($password);

            $structure->setUserAdmin($user);

            // Ajout de fonctionnalités à la structure
            foreach ($this->permissions() as $permission) {
                $structure->addStructurePermission($permission);
            }

            $manager->persist($structure);
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



    public function getDependencies()
    {
        return [
            FranchisesFixtures::class
        ];
    }

    public static function getGroups(): array
    {
        return ['structure'];
    }
}
