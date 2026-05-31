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

class ProposeExchangeControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }

    public function testProposeExchangeRequiresRoleCollector(): void
    {
        // Unauthenticated
        $this->client->request('GET', '/collector/exchange/propose/1');
        $this->assertResponseRedirects('/login');
    }

    public function testProposeExchangeForNonExistentItemReturns404(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('proposer_404@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($currentUser);
        $this->entityManager->flush();

        $this->client->loginUser($currentUser);
        $this->client->request('GET', '/collector/exchange/propose/999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testProposeExchangeForOwnItemRedirectsWithError(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('proposer_own@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($currentUser);
        $this->entityManager->flush();

        $game = (new Game())->setTitle('Zelda: Ocarina of Time')->setConsole('N64')->setReleaseYear(1998)->setIsHidden(false);
        $this->entityManager->persist($game);

        $ownItem = (new CollectionItem())
            ->setCollector($currentUser)
            ->setGame($game)
            ->setState(CollectionItemStates::MINT)
            ->setAcquisitionPrice(5000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($ownItem);
        $this->entityManager->flush();

        $this->client->loginUser($currentUser);
        $this->client->request('GET', '/collector/exchange/propose/' . $ownItem->getId());

        $this->assertResponseRedirects('/collector/exchange/search');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testProposeExchangeSuccess(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('proposer_success@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $otherUser = (new User())->setEmail('receiver_success@example.com');
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        $otherUser->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($currentUser);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $game1 = (new Game())->setTitle('Zelda: Ocarina of Time')->setConsole('N64')->setReleaseYear(1998)->setIsHidden(false);
        $game2 = (new Game())->setTitle('Super Mario 64')->setConsole('N64')->setReleaseYear(1996)->setIsHidden(false);

        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);

        $myAvailableItem = (new CollectionItem())
            ->setCollector($currentUser)
            ->setGame($game1)
            ->setState(CollectionItemStates::GOOD)
            ->setAcquisitionPrice(4500)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $receiverItem = (new CollectionItem())
            ->setCollector($otherUser)
            ->setGame($game2)
            ->setState(CollectionItemStates::MINT)
            ->setAcquisitionPrice(6000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($myAvailableItem);
        $this->entityManager->persist($receiverItem);
        $this->entityManager->flush();

        $this->client->loginUser($currentUser);
        $crawler = $this->client->request('GET', '/collector/exchange/propose/' . $receiverItem->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Super Mario 64');
        $this->assertSelectorTextContains('strong', 'receiver_success@example.com');

        $form = $crawler->selectButton('Envoyer la proposition d\'échange')->form();
        $form['exchange[offeredItems]'] = [$myAvailableItem->getId()];

        $this->client->submit($form);

        $this->assertResponseRedirects('/collector/exchange/search');

        // Check the database
        $exchanges = $this->entityManager->getRepository(Exchange::class)->findAll();
        $this->assertCount(1, $exchanges);

        /** @var Exchange $exchange */
        $exchange = $exchanges[0];
        $this->assertEquals(ExchangeStatuses::PENDING, $exchange->getStatus());
        $this->assertEquals($currentUser->getId(), $exchange->getProposer()->getId());
        $this->assertEquals($otherUser->getId(), $exchange->getReceiver()->getId());
        $this->assertCount(2, $exchange->getItems());

        $itemIds = array_map(fn($item) => $item->getId(), $exchange->getItems()->toArray());
        $this->assertContains($myAvailableItem->getId(), $itemIds);
        $this->assertContains($receiverItem->getId(), $itemIds);
    }

    public function testProposeExchangeValidationErrorNoItem(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('proposer_val@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $otherUser = (new User())->setEmail('receiver_val@example.com');
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        $otherUser->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($currentUser);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $game1 = (new Game())->setTitle('Zelda: Ocarina of Time')->setConsole('N64')->setReleaseYear(1998)->setIsHidden(false);
        $game2 = (new Game())->setTitle('Super Mario 64')->setConsole('N64')->setReleaseYear(1996)->setIsHidden(false);

        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);

        $myAvailableItem = (new CollectionItem())
            ->setCollector($currentUser)
            ->setGame($game1)
            ->setState(CollectionItemStates::GOOD)
            ->setAcquisitionPrice(4500)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $receiverItem = (new CollectionItem())
            ->setCollector($otherUser)
            ->setGame($game2)
            ->setState(CollectionItemStates::MINT)
            ->setAcquisitionPrice(6000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($myAvailableItem);
        $this->entityManager->persist($receiverItem);
        $this->entityManager->flush();

        $this->client->loginUser($currentUser);
        $crawler = $this->client->request('GET', '/collector/exchange/propose/' . $receiverItem->getId());

        $form = $crawler->selectButton('Envoyer la proposition d\'échange')->form();
        // Send with empty array
        $form['exchange[offeredItems]'] = [];

        $crawler = $this->client->submit($form);

        $this->assertSelectorTextContains('.invalid-feedback', 'Vous devez proposer au moins un jeu de votre collection en échange.');
    }

    public function testProposeExchangeDisplaysPendingExchanges(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('proposer_pending@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $otherUser = (new User())->setEmail('receiver_pending@example.com');
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        $otherUser->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($currentUser);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $game1 = (new Game())->setTitle('Zelda: Ocarina of Time')->setConsole('N64')->setReleaseYear(1998)->setIsHidden(false);
        $game2 = (new Game())->setTitle('Super Mario 64')->setConsole('N64')->setReleaseYear(1996)->setIsHidden(false);

        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);

        $myAvailableItem = (new CollectionItem())
            ->setCollector($currentUser)
            ->setGame($game1)
            ->setState(CollectionItemStates::GOOD)
            ->setAcquisitionPrice(4500)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $receiverItem = (new CollectionItem())
            ->setCollector($otherUser)
            ->setGame($game2)
            ->setState(CollectionItemStates::MINT)
            ->setAcquisitionPrice(6000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($myAvailableItem);
        $this->entityManager->persist($receiverItem);
        $this->entityManager->flush();

        // Create an existing pending exchange
        $existingExchange = new Exchange();
        $existingExchange->setProposer($currentUser);
        $existingExchange->setReceiver($otherUser);
        $existingExchange->setStatus(ExchangeStatuses::PENDING);
        $existingExchange->setPropositionDate(new \DateTime('2026-05-30'));
        $existingExchange->addItem($myAvailableItem);
        $existingExchange->addItem($receiverItem);

        $this->entityManager->persist($existingExchange);
        $this->entityManager->flush();

        $this->client->loginUser($currentUser);
        $crawler = $this->client->request('GET', '/collector/exchange/propose/' . $receiverItem->getId());

        $this->assertResponseIsSuccessful();
        
        // Verify that the pending exchange is displayed
        $this->assertSelectorTextContains('h4', 'Proposition(s) en cours pour ce jeu');
        $this->assertSelectorTextContains('.card-body', 'Vous avez proposé d\'offrir :');
        $this->assertSelectorTextContains('.card-body', 'Zelda: Ocarina of Time (N64)');
        $this->assertSelectorTextContains('.card-body', 'Proposition du 30/05/2026');
    }
}
