<?php

namespace App\Tests\Controller\Collector\Exchange;

use App\Entity\CollectionItem;
use App\Entity\Exchange;
use App\Entity\Game;
use App\Entity\User;
use App\Enum\CollectionItemStates;
use App\Enum\Currency;
use App\Enum\ExchangeStatuses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class ReceivedExchangesControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }

    public function testListReceivedExchangesRequiresAuthentication(): void
    {
        $this->client->request('GET', '/collector/exchange/received');
        $this->assertResponseRedirects('/login');
    }

    public function testListReceivedExchangesSuccess(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('me_received@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $otherUser = (new User())->setEmail('proposer_received@example.com');
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        $otherUser->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($currentUser);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $game1 = (new Game())->setTitle('Zelda: Ocarina of Time')->setConsole('N64')->setReleaseYear(1998)->setIsHidden(false);
        $game2 = (new Game())->setTitle('Super Mario 64')->setConsole('N64')->setReleaseYear(1996)->setIsHidden(false);

        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);

        $otherUserOfferedItem = (new CollectionItem())
            ->setCollector($otherUser)
            ->setGame($game1)
            ->setState(CollectionItemStates::GOOD)
            ->setAcquisitionPrice(4500)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $myRequestedItem = (new CollectionItem())
            ->setCollector($currentUser)
            ->setGame($game2)
            ->setState(CollectionItemStates::MINT)
            ->setAcquisitionPrice(6000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($otherUserOfferedItem);
        $this->entityManager->persist($myRequestedItem);
        $this->entityManager->flush();

        $exchange = (new Exchange())
            ->setProposer($otherUser)
            ->setReceiver($currentUser)
            ->setStatus(ExchangeStatuses::PENDING)
            ->setPropositionDate(new \DateTime())
            ->addItem($otherUserOfferedItem)
            ->addItem($myRequestedItem);

        $this->entityManager->persist($exchange);
        $this->entityManager->flush();

        $this->client->loginUser($currentUser);
        $this->client->request('GET', '/collector/exchange/received');

        self::assertResponseIsSuccessful();

        $html = $this->client->getResponse()->getContent();
        $crawler = new Crawler($html);
        $div = $crawler->filter('[data-symfony--ux-vue--vue-component-value="ReceivedExchanges"]');
        self::assertCount(1, $div);

        $props = json_decode($div->attr('data-symfony--ux-vue--vue-props-value'), true);
        self::assertArrayHasKey('exchanges', $props);
        self::assertCount(1, $props['exchanges']);

        $exData = $props['exchanges'][0];
        self::assertEquals(ExchangeStatuses::PENDING->value, $exData['status']);
        self::assertEquals('proposer_received@example.com', $exData['proposer']['email']);
        self::assertCount(1, $exData['offeredItems']);
        self::assertCount(1, $exData['requestedItems']);
        self::assertEquals('Zelda: Ocarina of Time', $exData['offeredItems'][0]['game']['title']);
        self::assertEquals('Super Mario 64', $exData['requestedItems'][0]['game']['title']);
    }
}
