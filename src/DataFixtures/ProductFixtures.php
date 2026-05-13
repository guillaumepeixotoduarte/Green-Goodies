<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\DataFixtures\UserFixtures;
use App\Entity\Product;
use Faker\Factory;

class ProductFixtures extends Fixture
{

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 10; $i++) {
            $imageNumber = mt_rand(1, 9);

            $product = new Product();
            $product->setName($faker->sentence(3));
            $product->setShortDescription($faker->text(50));
            $product->setFullDescription($faker->text(400));
            $product->setPrice($faker->randomFloat(0, 10, 50));
            $product->setPicture('img_' . $imageNumber . '.jpg');

            $manager->persist($product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProductFixtures::class,
        ];
    }
}
