<?php

namespace App\Tests\Controller\Collector\Exchange;

use App\Entity\CollectionItem;
use App\Entity\Exchange;
use App\Entity\Game;
use App\Entity\User;
use App\Enum\CollectionItemStates;
use App\Enum\Currency;
use App\Enum\ExchangeStatuses;
use Symfony\Component\DomCrawler\Crawler;
use App\Tests\DatabaseWebTestCase;

class SearchExchangeItemsControllerTest extends DatabaseWebTestCase
{


    public function testExchangeSearchSuccess(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('me@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $otherUser = (new User())->setEmail('other_collector@example.com');
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        $otherUser->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($currentUser);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $gameNormal = (new Game())->setTitle('Zelda: Ocarina of Time')->setConsole('N64')->setReleaseYear(1998)->setIsHidden(false);
        $gameHidden = (new Game())->setTitle('Hidden Game')->setConsole('NES')->setReleaseYear(1990)->setIsHidden(true);

        $nbGamesTradableBeforeAdd = $this->entityManager->getRepository(CollectionItem::class)->countAvailableForExchange($currentUser);

        $this->entityManager->persist($gameNormal);
        $this->entityManager->persist($gameHidden);

        $itemOwnedByMe = (new CollectionItem())
            ->setCollector($currentUser)
            ->setGame($gameNormal)
            ->setState(CollectionItemStates::MINT)
            ->setAcquisitionPrice(5000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $itemAvailable = (new CollectionItem())
            ->setCollector($otherUser)
            ->setGame($gameNormal)
            ->setState(CollectionItemStates::GOOD)
            ->setAcquisitionPrice(4000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $itemHiddenGame = (new CollectionItem())
            ->setCollector($otherUser)
            ->setGame($gameHidden)
            ->setState(CollectionItemStates::GOOD)
            ->setAcquisitionPrice(3000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $itemPending = (new CollectionItem())
            ->setCollector($otherUser)
            ->setGame($gameNormal)
            ->setState(CollectionItemStates::FAIR)
            ->setAcquisitionPrice(2000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $itemRejected = (new CollectionItem())
            ->setCollector($otherUser)
            ->setGame($gameNormal)
            ->setState(CollectionItemStates::POOR)
            ->setAcquisitionPrice(1000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($itemOwnedByMe);
        $this->entityManager->persist($itemAvailable);
        $this->entityManager->persist($itemHiddenGame);
        $this->entityManager->persist($itemPending);
        $this->entityManager->persist($itemRejected);
        $this->entityManager->flush();

        $exchangePending = (new Exchange())
            ->setProposer($currentUser)
            ->setReceiver($otherUser)
            ->setStatus(ExchangeStatuses::PENDING)
            ->setPropositionDate(new \DateTime())
            ->addItem($itemPending);

        $exchangeRejected = (new Exchange())
            ->setProposer($currentUser)
            ->setReceiver($otherUser)
            ->setStatus(ExchangeStatuses::REJECTED)
            ->setPropositionDate(new \DateTime())
            ->addItem($itemRejected);

        $this->entityManager->persist($exchangePending);
        $this->entityManager->persist($exchangeRejected);
        $this->entityManager->flush();

        $this->client->loginUser($currentUser);
        $this->client->request('GET', '/collector/exchange/search');

        self::assertResponseIsSuccessful();

        $html = $this->client->getResponse()->getContent();
        $crawler = new Crawler($html);
        $div = $crawler->filter('[data-symfony--ux-vue--vue-component-value="pages/ExchangeSearch"]');
        self::assertCount(1, $div);

        $props = json_decode($div->attr('data-symfony--ux-vue--vue-props-value'), true);
        self::assertArrayHasKey('items', $props);
        
        $itemsData = $props['items'];
        
        self::assertCount(3 + $nbGamesTradableBeforeAdd, $itemsData);

        $availableItemData = null;
        $rejectedItemData = null;
        foreach ($itemsData as $data) {
            if ($data['id'] === $itemAvailable->getId()) {
                $availableItemData = $data;
            } elseif ($data['id'] === $itemRejected->getId()) {
                $rejectedItemData = $data;
            }
        }

        self::assertNotNull($availableItemData);
        self::assertNotNull($rejectedItemData);

        self::assertEquals(CollectionItemStates::GOOD->value, $availableItemData['state']);
        self::assertEquals(4000, $availableItemData['acquisitionPrice']);
        self::assertEquals('Zelda: Ocarina of Time', $availableItemData['game']['title']);

        self::assertEquals(CollectionItemStates::POOR->value, $rejectedItemData['state']);
        self::assertEquals(1000, $rejectedItemData['acquisitionPrice']);
        self::assertEquals('Zelda: Ocarina of Time', $rejectedItemData['game']['title']);
    }
}
