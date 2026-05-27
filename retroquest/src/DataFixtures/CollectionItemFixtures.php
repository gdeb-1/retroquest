<?php

namespace App\DataFixtures;

use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CollectionItemFixtures extends Fixture implements DependentFixtureInterface
{
    public const COLLECTION_ITEM_REFERENCE_PREFIX = 'collection_item_';

    public function load(ObjectManager $manager): void
    {
        $states = ['Mint', 'Good', 'Fair', 'Poor'];
        $currencies = ['EUR', 'USD'];

        for ($i = 0; $i < 50; $i++) {
            $item = new CollectionItem();
            
            $item->setState($states[array_rand($states)]);
            $item->setAcquisitionPrice(rand(500, 12000));
            $item->setCurrency($currencies[array_rand($currencies)]);
            $item->setAcquisitionDate(new \DateTime(sprintf('-%d days', rand(1, 730))));
            
            // Random Game
            $gameReference = GameFixtures::GAME_REFERENCE_PREFIX . rand(0, 99);
            $game = $this->getReference($gameReference, Game::class);
            $item->setGame($game);
            
            // Random Collector (among the 10 collectors)
            $collectorReference = UserFixtures::COLLECTOR_REFERENCE_PREFIX . rand(0, 9);
            $collector = $this->getReference($collectorReference, User::class);
            $item->setCollector($collector);
            
            $manager->persist($item);
            
            $this->addReference(self::COLLECTION_ITEM_REFERENCE_PREFIX . $i, $item);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            GameFixtures::class,
            UserFixtures::class,
        ];
    }
}
