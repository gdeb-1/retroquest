<?php

namespace App\DataFixtures;

use App\Entity\Exchange;
use App\Entity\CollectionItem;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ExchangeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $statuses = ['pending', 'accepted', 'rejected', 'cancelled'];

        // 1. Group collection items by collector's email
        $itemsByCollector = [];
        for ($j = 0; $j < 50; $j++) {
            $item = $this->getReference(CollectionItemFixtures::COLLECTION_ITEM_REFERENCE_PREFIX . $j, CollectionItem::class);
            $collector = $item->getCollector();
            if ($collector !== null) {
                $itemsByCollector[$collector->getEmail()][] = $item;
            }
        }

        // Get the list of collectors who have items
        $collectorsWithItems = [];
        foreach ($itemsByCollector as $email => $items) {
            $collectorsWithItems[] = $items[0]->getCollector();
        }

        if (empty($collectorsWithItems)) {
            return;
        }

        // 2. Generate 20 Exchanges
        for ($i = 0; $i < 20; $i++) {
            $exchange = new Exchange();
            
            // Pick proposer
            $proposer = $collectorsWithItems[array_rand($collectorsWithItems)];
            $exchange->setProposer($proposer);
            
            // Pick receiver (different collector from the 10 collectors)
            $receiverIndex = rand(0, 9);
            $receiver = $this->getReference(UserFixtures::COLLECTOR_REFERENCE_PREFIX . $receiverIndex, User::class);
            
            // Ensure receiver is different from proposer
            while ($receiver->getEmail() === $proposer->getEmail()) {
                $receiverIndex = rand(0, 9);
                $receiver = $this->getReference(UserFixtures::COLLECTOR_REFERENCE_PREFIX . $receiverIndex, User::class);
            }
            $exchange->setReceiver($receiver);
            
            // Random status
            $exchange->setStatus($statuses[array_rand($statuses)]);
            
            // Random proposition date in the last year
            $exchange->setPropositionDate(new \DateTime(sprintf('-%d days', rand(1, 365))));
            
            // Add items from proposer
            $proposerItems = $itemsByCollector[$proposer->getEmail()] ?? [];
            $numProposerItems = min(rand(1, 2), count($proposerItems));
            if ($numProposerItems > 0) {
                $chosenProposerKeys = (array) array_rand($proposerItems, $numProposerItems);
                foreach ($chosenProposerKeys as $key) {
                    $exchange->addItem($proposerItems[$key]);
                }
            }
            
            // Add items from receiver (optional)
            $receiverItems = $itemsByCollector[$receiver->getEmail()] ?? [];
            if (!empty($receiverItems)) {
                $numReceiverItems = min(rand(0, 2), count($receiverItems));
                if ($numReceiverItems > 0) {
                    $chosenReceiverKeys = (array) array_rand($receiverItems, $numReceiverItems);
                    foreach ($chosenReceiverKeys as $key) {
                        $exchange->addItem($receiverItems[$key]);
                    }
                }
            }
            
            $manager->persist($exchange);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CollectionItemFixtures::class,
            UserFixtures::class,
        ];
    }
}
