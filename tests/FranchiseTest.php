<?php

namespace App\Tests;

use App\Entity\Franchise;
use App\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class FranchiseTest extends TestCase
{
    /**
     * Test la création d'une franchise avec son propriétaire.
     */
    public function testCreateFranchise(): void
    {
        // On crée une franchise
        $franchise = new Franchise();
        $franchise->setName('Ma Franchise');
        $franchise->setSlug('ma-franchise');

        // On teste que la franchise a bien été créée avec les bonnes valeurs
        $this->assertEquals('Ma Franchise', $franchise->getname());
        $this->assertEquals('ma-franchise', $franchise->getSlug());
        $this->assertTrue($franchise->isActive());
        $this->assertInstanceOf(DateTimeImmutable::class, $franchise->getCreateAt());

        // On crée le propriétaire de la franchise
        $userOwner = new User();
        $userOwner->setFirstname('John');
        $userOwner->setLastname('Doe');
        $userOwner->setEmail('johndoe@gmail.com');
        $userOwner->setPhone('0789685478');
        $userOwner->setRoles(['ROLE_FRANCHISE']);

        // On teste que l'utilisateur a bien été crée avec les bonnes valeurs
        $this->assertEquals('John', $userOwner->getFirstname());
        $this->assertEquals('Doe', $userOwner->getLastname());
        $this->assertEquals('johndoe@gmail.com', $userOwner->getEmail());
        $this->assertEquals('0789685478', $userOwner->getPhone());
        $this->assertContains('ROLE_FRANCHISE', $userOwner->getRoles());
        $this->assertFalse($userOwner->isActive());
        $this->assertInstanceOf(DateTimeImmutable::class, $userOwner->getCreateAt());
        $this->assertEquals('avatar-defaut.jpg', $userOwner->getAvatar());
        $this->assertNotEmpty($userOwner->getActivationToken());

        // On assigne l'utilisateur à la franchise
        $franchise->setUserOwner($userOwner);
        $this->assertInstanceOf(User::class, $franchise->getUserOwner());
        
    }


}
