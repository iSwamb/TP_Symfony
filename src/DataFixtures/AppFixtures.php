<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        // Create 20 users
        for($i = 0; $i < 20; $i++) {
            $user = new User();
            $user->setEmail("clem@gmail.com");
            $user->setUsername("clem9519");
            $user->setFirstname("Clément");
            $user->setLastname("LE CARO");
            $user->setJobtitle("Web Developper");
            $user->setEnabled(true);
            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());
        }

        $manager->flush();
    }
}
