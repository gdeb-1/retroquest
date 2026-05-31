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
use Symfony\Component\HttpFoundation\RequestStack;

class SentExchangesControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }

    public function testListSentExchangesRequiresAuthentication(): void
    {
        $this->client->request('GET', '/collector/exchange/sent');
        $this->assertResponseRedirects('/login');
    }

    public function testListSentExchangesSuccess(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('me_sent@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $otherUser = (new User())->setEmail('receiver_sent@example.com');
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        $otherUser->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($currentUser);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $game1 = (new Game())->setTitle('Zelda: Ocarina of Time')->setConsole('N64')->setReleaseYear(1998)->setIsHidden(false);
        $game2 = (new Game())->setTitle('Super Mario 64')->setConsole('N64')->setReleaseYear(1996)->setIsHidden(false);

        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);

        $myOfferedItem = (new CollectionItem())
            ->setCollector($currentUser)
            ->setGame($game1)
            ->setState(CollectionItemStates::GOOD)
            ->setAcquisitionPrice(4500)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $receiverRequestedItem = (new CollectionItem())
            ->setCollector($otherUser)
            ->setGame($game2)
            ->setState(CollectionItemStates::MINT)
            ->setAcquisitionPrice(6000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($myOfferedItem);
        $this->entityManager->persist($receiverRequestedItem);
        $this->entityManager->flush();

        $exchange = (new Exchange())
            ->setProposer($currentUser)
            ->setReceiver($otherUser)
            ->setStatus(ExchangeStatuses::PENDING)
            ->setPropositionDate(new \DateTime())
            ->addItem($myOfferedItem)
            ->addItem($receiverRequestedItem);

        $this->entityManager->persist($exchange);
        $this->entityManager->flush();

        $this->client->loginUser($currentUser);
        $this->client->request('GET', '/collector/exchange/sent');

        self::assertResponseIsSuccessful();

        $html = $this->client->getResponse()->getContent();
        $crawler = new Crawler($html);
        $div = $crawler->filter('[data-symfony--ux-vue--vue-component-value="SentExchanges"]');
        self::assertCount(1, $div);

        $props = json_decode($div->attr('data-symfony--ux-vue--vue-props-value'), true);
        self::assertArrayHasKey('exchanges', $props);
        self::assertCount(1, $props['exchanges']);

        $exData = $props['exchanges'][0];
        self::assertEquals(ExchangeStatuses::PENDING->value, $exData['status']);
        self::assertEquals('receiver_sent@example.com', $exData['receiver']['email']);
        self::assertCount(1, $exData['offeredItems']);
        self::assertCount(1, $exData['requestedItems']);
        self::assertEquals('Zelda: Ocarina of Time', $exData['offeredItems'][0]['game']['title']);
        self::assertEquals('Super Mario 64', $exData['requestedItems'][0]['game']['title']);
    }

    public function testCancelExchangeSuccess(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('me_cancel@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $otherUser = (new User())->setEmail('receiver_cancel@example.com');
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        $otherUser->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($currentUser);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $game1 = (new Game())->setTitle('Zelda: Ocarina of Time')->setConsole('N64')->setReleaseYear(1998)->setIsHidden(false);
        $game2 = (new Game())->setTitle('Super Mario 64')->setConsole('N64')->setReleaseYear(1996)->setIsHidden(false);

        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);

        $myOfferedItem = (new CollectionItem())
            ->setCollector($currentUser)
            ->setGame($game1)
            ->setState(CollectionItemStates::GOOD)
            ->setAcquisitionPrice(4500)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $receiverRequestedItem = (new CollectionItem())
            ->setCollector($otherUser)
            ->setGame($game2)
            ->setState(CollectionItemStates::MINT)
            ->setAcquisitionPrice(6000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($myOfferedItem);
        $this->entityManager->persist($receiverRequestedItem);
        $this->entityManager->flush();

        $exchange = (new Exchange())
            ->setProposer($currentUser)
            ->setReceiver($otherUser)
            ->setStatus(ExchangeStatuses::PENDING)
            ->setPropositionDate(new \DateTime())
            ->addItem($myOfferedItem)
            ->addItem($receiverRequestedItem);

        $this->entityManager->persist($exchange);
        $this->entityManager->flush();

        $this->client->loginUser($currentUser);
        $this->client->request('GET', '/collector/exchange/sent');
        self::assertResponseIsSuccessful();

        $html = $this->client->getResponse()->getContent();
        $crawler = new Crawler($html);
        $div = $crawler->filter('[data-symfony--ux-vue--vue-component-value="SentExchanges"]');
        $props = json_decode($div->attr('data-symfony--ux-vue--vue-props-value'), true);
        $token = $props['exchanges'][0]['csrfTokenCancel'];

        // Perform cancellation
        $this->client->request('POST', '/collector/exchange/cancel/' . $exchange->getId(), [
            '_token' => $token
        ]);

        self::assertResponseRedirects('/collector/exchange/sent');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'La proposition d\'échange a bien été annulée.');

        // Check DB
        $this->entityManager->clear();
        $updatedExchange = $this->entityManager->getRepository(Exchange::class)->find($exchange->getId());
        self::assertEquals(ExchangeStatuses::CANCELLED, $updatedExchange->getStatus());
    }

    public function testCancelExchangeAccessDeniedForNonProposer(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('me_cancel_denied@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $otherUser = (new User())->setEmail('receiver_cancel_denied@example.com');
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        $otherUser->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($currentUser);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $game1 = (new Game())->setTitle('Zelda: Ocarina of Time')->setConsole('N64')->setReleaseYear(1998)->setIsHidden(false);
        $game2 = (new Game())->setTitle('Super Mario 64')->setConsole('N64')->setReleaseYear(1996)->setIsHidden(false);

        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);

        $myOfferedItem = (new CollectionItem())
            ->setCollector($currentUser)
            ->setGame($game1)
            ->setState(CollectionItemStates::GOOD)
            ->setAcquisitionPrice(4500)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $receiverRequestedItem = (new CollectionItem())
            ->setCollector($otherUser)
            ->setGame($game2)
            ->setState(CollectionItemStates::MINT)
            ->setAcquisitionPrice(6000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($myOfferedItem);
        $this->entityManager->persist($receiverRequestedItem);
        $this->entityManager->flush();

        $exchange = (new Exchange())
            ->setProposer($currentUser)
            ->setReceiver($otherUser)
            ->setStatus(ExchangeStatuses::PENDING)
            ->setPropositionDate(new \DateTime())
            ->addItem($myOfferedItem)
            ->addItem($receiverRequestedItem);

        $this->entityManager->persist($exchange);
        $this->entityManager->flush();

        // Login as the receiver of the exchange, not the proposer
        $this->client->loginUser($otherUser);

        // Initialize session
        $this->client->request('GET', '/collector/exchange/sent');
        $request = $this->client->getRequest();
        static::getContainer()->get(RequestStack::class)->push($request);

        // Generate token for otherUser (using container csrf token manager)
        $token = static::getContainer()->get('security.csrf.token_manager')->getToken('cancel_exchange_' . $exchange->getId())->getValue();
        $request->getSession()->save();
        static::getContainer()->get(RequestStack::class)->pop();

        // Perform cancellation request - should deny access
        $this->client->request('POST', '/collector/exchange/cancel/' . $exchange->getId(), [
            '_token' => $token
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        
        // Verify DB unchanged
        $this->entityManager->clear();
        $updatedExchange = $this->entityManager->getRepository(Exchange::class)->find($exchange->getId());
        self::assertEquals(ExchangeStatuses::PENDING, $updatedExchange->getStatus());
    }

    public function testCancelExchangeNonPendingFails(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('me_cancel_nonpending@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $otherUser = (new User())->setEmail('receiver_cancel_nonpending@example.com');
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        $otherUser->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($currentUser);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $game1 = (new Game())->setTitle('Zelda: Ocarina of Time')->setConsole('N64')->setReleaseYear(1998)->setIsHidden(false);
        $game2 = (new Game())->setTitle('Super Mario 64')->setConsole('N64')->setReleaseYear(1996)->setIsHidden(false);

        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);

        $myOfferedItem = (new CollectionItem())
            ->setCollector($currentUser)
            ->setGame($game1)
            ->setState(CollectionItemStates::GOOD)
            ->setAcquisitionPrice(4500)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $receiverRequestedItem = (new CollectionItem())
            ->setCollector($otherUser)
            ->setGame($game2)
            ->setState(CollectionItemStates::MINT)
            ->setAcquisitionPrice(6000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($myOfferedItem);
        $this->entityManager->persist($receiverRequestedItem);
        $this->entityManager->flush();

        // Create an accepted exchange
        $exchange = (new Exchange())
            ->setProposer($currentUser)
            ->setReceiver($otherUser)
            ->setStatus(ExchangeStatuses::ACCEPTED)
            ->setPropositionDate(new \DateTime())
            ->addItem($myOfferedItem)
            ->addItem($receiverRequestedItem);

        $this->entityManager->persist($exchange);
        $this->entityManager->flush();

        $this->client->loginUser($currentUser);
        $this->client->request('GET', '/collector/exchange/sent');
        $request = $this->client->getRequest();
        static::getContainer()->get(RequestStack::class)->push($request);
        $token = static::getContainer()->get('security.csrf.token_manager')->getToken('cancel_exchange_' . $exchange->getId())->getValue();
        $request->getSession()->save();
        static::getContainer()->get(RequestStack::class)->pop();

        // Perform cancellation request
        $this->client->request('POST', '/collector/exchange/cancel/' . $exchange->getId(), [
            '_token' => $token
        ]);

        self::assertResponseRedirects('/collector/exchange/sent');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', "Cet échange ne peut pas être annulé car il n'est plus en attente.");

        // Verify DB unchanged
        $this->entityManager->clear();
        $updatedExchange = $this->entityManager->getRepository(Exchange::class)->find($exchange->getId());
        self::assertEquals(ExchangeStatuses::ACCEPTED, $updatedExchange->getStatus());
    }
}
