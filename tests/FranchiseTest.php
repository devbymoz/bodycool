<?php

namespace App\Tests;

use App\Entity\Franchise;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FranchiseTest extends KernelTestCase
{
    public function testSomething(): void
    {
        self::bootKernel();
        $container = static::getContainer();

/*         $franchise = new Franchise();
        $franchise->setName('M');
        $franchise->setSlug('ma-franchise');
        $franchise->setActive(true);

        $errors = $container->get('validator')->validator($franchise);

        $this->assertCount(0, $errors);
         */

    }
}
