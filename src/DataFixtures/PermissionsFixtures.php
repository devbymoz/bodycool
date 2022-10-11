<?php

namespace App\DataFixtures;

use App\Entity\Permission;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class PermissionsFixtures extends Fixture implements FixtureGroupInterface
{
    // Création de 7 permissions
    public function load(ObjectManager $manager): void
    {
        $permission1 = new Permission();
        $permission1->setName('Gestion planning d’équipe');
        $permission1->setDescription('Pellentesque molestie risus vel urna volutpat, a consectetur quam lacinia. morbi pharetra lacus et rhoncus sodales.');
        $manager->persist($permission1);

        $permission2 = new Permission();
        $permission2->setName('Envoi de newsletter');
        $permission2->setDescription('Donec a dui hendrerit, euismod ipsum a, dictum mi. sed at eros ex. proin justo odio, accumsan id justo in, molestie interdum odio.');
        $manager->persist($permission2);

        $permission3 = new Permission();
        $permission3->setName('Vente de boissons');
        $permission3->setDescription('Morbi in ullamcorper quam, a ornare eros. nullam eleifend magna a turpis luctus dictum. donec tristique lacus sapien, et tempus diam.');
        $manager->persist($permission3);

        $permission4 = new Permission();
        $permission4->setName('Vente de compléments alimentaires');
        $permission4->setDescription('Quisque vitae venenatis lorem. vivamus tortor lectus, mollis quis dapibus vitae, ultrices vitae eros. vivamus et ex quam. nullam elementum, erat quis pharetra luctus.');
        $manager->persist($permission4);

        $permission5 = new Permission();
        $permission5->setName('Vente de programmes sportifs');
        $permission5->setDescription('Lorem ipsum dolor sit amet, consectetur adipiscing elit. etiam finibus ex ut sem sollicitudin commodo. praesent elit nibh, lacinia sit amet ligula nec.');
        $manager->persist($permission5);

        $permission6 = new Permission();
        $permission6->setName('Assistance technique');
        $permission6->setDescription('Fusce vulputate tortor quis ornare rutrum. aliquam feugiat nisi nunc, id hendrerit sapien faucibus in.');
        $manager->persist($permission6);   

        $permission7 = new Permission();
        $permission7->setName('Vente de programmes nutritionnels');
        $permission7->setDescription('Aliquam sit amet ex ac felis sagittis commodo a vestibulum velit. class aptent taciti sociosqu ad litora torquent per conubia nostra.');
        $manager->persist($permission7);   

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['permission'];
    }
}
