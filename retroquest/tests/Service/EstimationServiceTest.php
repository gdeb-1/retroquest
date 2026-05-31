<?php

namespace App\Tests\Service;

use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\User;
use App\Enum\CollectionItemStates;
use App\Enum\Currency;
use App\Repository\CollectionItemRepository;
use App\Service\EstimationService;
use PHPUnit\Framework\TestCase;

class EstimationServiceTest extends TestCase
{
    private CollectionItemRepository $collectionItemRepository;
    private EstimationService $estimationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collectionItemRepository = $this->createMock(CollectionItemRepository::class);
        $this->estimationService = new EstimationService($this->collectionItemRepository);
    }

    public function testCalculateCollectionValueWithEmptyCollection(): void
    {
        $collector = new User();

        $this->collectionItemRepository
            ->expects($this->never())
            ->method('findForGames');

        $result = $this->estimationService->calculateCollectionValue($collector);

        // All currencies should be 0
        $this->assertSame([
            'EUR' => 0,
            'USD' => 0,
            'GBP' => 0,
        ], $result);
    }

    public function testCalculateCollectionValueUsesStateSpecificAverage(): void
    {
        $collector = new User();

        $game1 = new Game();
        $this->setEntityId($game1, 1);

        $game2 = new Game();
        $this->setEntityId($game2, 2);

        // Collector items
        $item1 = new CollectionItem();
        $item1->setGame($game1);
        $item1->setState(CollectionItemStates::MINT);
        $item1->setCurrency(Currency::EUR);
        $item1->setAcquisitionPrice(10000); // 100 EUR
        $collector->addCollectionItem($item1);

        $item2 = new CollectionItem();
        $item2->setGame($game2);
        $item2->setState(CollectionItemStates::GOOD);
        $item2->setCurrency(Currency::EUR);
        $item2->setAcquisitionPrice(5000); // 50 EUR
        $collector->addCollectionItem($item2);

        $item3 = new CollectionItem();
        $item3->setGame($game1);
        $item3->setState(CollectionItemStates::POOR);
        $item3->setCurrency(Currency::USD);
        $item3->setAcquisitionPrice(3000); // 30 USD
        $collector->addCollectionItem($item3);

        // Other items in the DB to calculate averages
        // For game 1, MINT, EUR: we want an average of 113.33 EUR (11333)
        $dbItem1 = new CollectionItem();
        $dbItem1->setGame($game1);
        $dbItem1->setState(CollectionItemStates::MINT);
        $dbItem1->setCurrency(Currency::EUR);
        $dbItem1->setAcquisitionPrice(11000);

        $dbItem2 = new CollectionItem();
        $dbItem2->setGame($game1);
        $dbItem2->setState(CollectionItemStates::MINT);
        $dbItem2->setCurrency(Currency::EUR);
        $dbItem2->setAcquisitionPrice(13000);

        // For game 2, GOOD, EUR: we want an average of 55 EUR (5500)
        $dbItem3 = new CollectionItem();
        $dbItem3->setGame($game2);
        $dbItem3->setState(CollectionItemStates::GOOD);
        $dbItem3->setCurrency(Currency::EUR);
        $dbItem3->setAcquisitionPrice(6000);

        // For game 1, POOR, USD: we want an average of 26.67 USD (2667)
        $dbItem4 = new CollectionItem();
        $dbItem4->setGame($game1);
        $dbItem4->setState(CollectionItemStates::POOR);
        $dbItem4->setCurrency(Currency::USD);
        $dbItem4->setAcquisitionPrice(2000);

        $dbItem5 = new CollectionItem();
        $dbItem5->setGame($game1);
        $dbItem5->setState(CollectionItemStates::POOR);
        $dbItem5->setCurrency(Currency::USD);
        $dbItem5->setAcquisitionPrice(3000);

        $allMatchingItems = [$item1, $item2, $item3, $dbItem1, $dbItem2, $dbItem3, $dbItem4, $dbItem5];

        $this->collectionItemRepository
            ->expects($this->once())
            ->method('findForGames')
            ->with($this->callback(function (array $games) use ($game1, $game2) {
                return count($games) === 2 && in_array($game1, $games, true) && in_array($game2, $games, true);
            }))
            ->willReturn($allMatchingItems);

        $result = $this->estimationService->calculateCollectionValue($collector);

        // Expected values:
        // EUR:
        // - item1 (game1, MINT, EUR): average of (10000 + 11000 + 13000) / 3 = 11333
        // - item2 (game2, GOOD, EUR): average of (5000 + 6000) / 2 = 5500
        // Total EUR = 11333 + 5500 = 16833
        //
        // USD:
        // - item3 (game1, POOR, USD): average of (3000 + 2000 + 3000) / 3 = 2667
        // Total USD = 2667
        //
        // GBP:
        // - none: 0
        $this->assertEquals([
            'EUR' => 16833,
            'USD' => 2667,
            'GBP' => 0,
        ], $result);
    }

    public function testCalculateCollectionValueFallbackToAcquisitionPrice(): void
    {
        $collector = new User();

        $game1 = new Game();
        $this->setEntityId($game1, 1);

        $item1 = new CollectionItem();
        $item1->setGame($game1);
        $item1->setState(CollectionItemStates::MINT);
        $item1->setCurrency(Currency::EUR);
        $item1->setAcquisitionPrice(9999);
        $collector->addCollectionItem($item1);

        // Repository returns empty or doesn't have average for this specific combo
        $this->collectionItemRepository
            ->expects($this->once())
            ->method('findForGames')
            ->willReturn([]);

        $result = $this->estimationService->calculateCollectionValue($collector);

        // Should fallback to item1's acquisition price (9999 EUR)
        $this->assertEquals([
            'EUR' => 9999,
            'USD' => 0,
            'GBP' => 0,
        ], $result);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}
