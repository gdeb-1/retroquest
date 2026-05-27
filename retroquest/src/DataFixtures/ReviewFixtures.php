<?php

namespace App\DataFixtures;

use App\Entity\Review;
use App\Entity\Game;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ReviewFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $comments = [
            'A masterpiece of the 8/16-bit era. The music is unforgettable!',
            'Graphically stunning for its time, but the controls can be a bit stiff.',
            'Spent countless hours playing this in my childhood. Still holds up today.',
            'The level design is top-notch. Truly a legendary game.',
            'Hard but fair. One of the best platformers ever created.',
            'An absolute classic. The gameplay mechanics are perfect.',
            'A bit overrated, but still a solid entry in the franchise.',
            'Unbelievable soundtrack and atmospheric vibes.',
            'A must-play for any retro gaming enthusiast.',
            'Decent game, but it hasn\'t aged very well.',
            'The story is incredible and the gameplay is deep.',
            'A true definition of nostalgia. Simply amazing.',
            'The bosses are extremely challenging but very satisfying to defeat.',
            'Revolutionary for its time, set the standard for the genre.',
            'A hidden gem that everyone should try at least once.',
        ];

        for ($i = 0; $i < 30; $i++) {
            $review = new Review();
            
            $review->setComment($comments[array_rand($comments)]);
            $review->setIsValid(rand(1, 100) <= 90);
            $review->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', rand(1, 180))));
            
            // Random Game
            $gameReference = GameFixtures::GAME_REFERENCE_PREFIX . rand(0, 99);
            $game = $this->getReference($gameReference, Game::class);
            $review->setGame($game);
            
            // Random Collector/User (author)
            $collectorReference = UserFixtures::COLLECTOR_REFERENCE_PREFIX . rand(0, 9);
            $collector = $this->getReference($collectorReference, User::class);
            $review->setAuthor($collector);
            
            $manager->persist($review);
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
