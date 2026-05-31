<?php

namespace App\Tests\Controller\Collector\Exchange;

use App\Entity\CollectionItem;
use App\Entity\Exchange;
use App\Entity\Game;
use App\Entity\User;
use App\Enum\CollectionItemStates;
use App\Enum\Currency;
use App\Enum\ExchangeStatuses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Tests\DatabaseWebTestCase;

class ValidateExchangeControllerTest extends DatabaseWebTestCase
{


    public function testValidateExchangeRequiresAuthentication(): void
    {
        $this->client->request('POST', '/collector/exchange/validate/1');
        $this->assertResponseRedirects('/login');
    }

    public function testValidateExchangeSuccess(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('receiver_validate@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $otherUser = (new User())->setEmail('proposer_validate@example.com');
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        $otherUser->setRoles(['ROLE_COLLECTOR']);

        $thirdUser = (new User())->setEmail('third_validate@example.com');
        $thirdUser->setPassword($passwordHasher->hashPassword($thirdUser, 'password'));
        $thirdUser->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($currentUser);
        $this->entityManager->persist($otherUser);
        $this->entityManager->persist($thirdUser);
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

        // Create a conflicting pending exchange with the third party that also contains $otherUserOfferedItem
        $otherExchange = (new Exchange())
            ->setProposer($otherUser)
            ->setReceiver($thirdUser)
            ->setStatus(ExchangeStatuses::PENDING)
            ->setPropositionDate(new \DateTime())
            ->addItem($otherUserOfferedItem);

        $this->entityManager->persist($exchange);
        $this->entityManager->persist($otherExchange);
        $this->entityManager->flush();

        $this->client->loginUser($currentUser);
        
        // Initialize session
        $this->client->request('GET', '/collector/exchange/received');
        $request = $this->client->getRequest();
        static::getContainer()->get(RequestStack::class)->push($request);

        // Generate CSRF Token
        $token = static::getContainer()->get('security.csrf.token_manager')->getToken('validate_exchange_' . $exchange->getId())->getValue();
        $request->getSession()->save();
        static::getContainer()->get(RequestStack::class)->pop();

        // Perform validation
        $this->client->request('POST', '/collector/exchange/validate/' . $exchange->getId(), [
            '_token' => $token
        ]);

        self::assertResponseRedirects('/collector/exchange/received');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'La proposition d\'échange a bien été validée et la propriété des objets a été transférée.');

        // Check DB
        $this->entityManager->clear();
        $updatedExchange = $this->entityManager->getRepository(Exchange::class)->find($exchange->getId());
        self::assertEquals(ExchangeStatuses::ACCEPTED, $updatedExchange->getStatus());

        $updatedConflictingExchange = $this->entityManager->getRepository(Exchange::class)->find($otherExchange->getId());
        self::assertEquals(ExchangeStatuses::CANCELLED, $updatedConflictingExchange->getStatus());

        $updatedItem1 = $this->entityManager->getRepository(CollectionItem::class)->find($otherUserOfferedItem->getId());
        $updatedItem2 = $this->entityManager->getRepository(CollectionItem::class)->find($myRequestedItem->getId());

        // Ownership should be swapped
        self::assertEquals($currentUser->getId(), $updatedItem1->getCollector()->getId());
        self::assertEquals($otherUser->getId(), $updatedItem2->getCollector()->getId());
    }

    public function testValidateExchangeAccessDeniedForNonReceiver(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('receiver_denied@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $otherUser = (new User())->setEmail('proposer_denied@example.com');
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

        // Login as the proposer of the exchange (otherUser), not the receiver (currentUser)
        $this->client->loginUser($otherUser);

        // Initialize session
        $this->client->request('GET', '/collector/exchange/received');
        $request = $this->client->getRequest();
        static::getContainer()->get(RequestStack::class)->push($request);

        // Generate token
        $token = static::getContainer()->get('security.csrf.token_manager')->getToken('validate_exchange_' . $exchange->getId())->getValue();
        $request->getSession()->save();
        static::getContainer()->get(RequestStack::class)->pop();

        // Perform validation request - should deny access
        $this->client->request('POST', '/collector/exchange/validate/' . $exchange->getId(), [
            '_token' => $token
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Verify DB unchanged
        $this->entityManager->clear();
        $updatedExchange = $this->entityManager->getRepository(Exchange::class)->find($exchange->getId());
        self::assertEquals(ExchangeStatuses::PENDING, $updatedExchange->getStatus());
    }

    public function testValidateExchangeNonPendingFails(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $currentUser = (new User())->setEmail('receiver_nonpending@example.com');
        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, 'password'));
        $currentUser->setRoles(['ROLE_COLLECTOR']);

        $otherUser = (new User())->setEmail('proposer_nonpending@example.com');
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

        // Create an already accepted exchange
        $exchange = (new Exchange())
            ->setProposer($otherUser)
            ->setReceiver($currentUser)
            ->setStatus(ExchangeStatuses::ACCEPTED)
            ->setPropositionDate(new \DateTime())
            ->addItem($otherUserOfferedItem)
            ->addItem($myRequestedItem);

        $this->entityManager->persist($exchange);
        $this->entityManager->flush();

        $this->client->loginUser($currentUser);
        
        // Initialize session
        $this->client->request('GET', '/collector/exchange/received');
        $request = $this->client->getRequest();
        static::getContainer()->get(RequestStack::class)->push($request);
        
        $token = static::getContainer()->get('security.csrf.token_manager')->getToken('validate_exchange_' . $exchange->getId())->getValue();
        $request->getSession()->save();
        static::getContainer()->get(RequestStack::class)->pop();

        // Perform validation request
        $this->client->request('POST', '/collector/exchange/validate/' . $exchange->getId(), [
            '_token' => $token
        ]);

        self::assertResponseRedirects('/collector/exchange/received');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Seuls les échanges en attente peuvent être validés.');

        // Verify DB unchanged
        $this->entityManager->clear();
        $updatedExchange = $this->entityManager->getRepository(Exchange::class)->find($exchange->getId());
        self::assertEquals(ExchangeStatuses::ACCEPTED, $updatedExchange->getStatus());
    }
}
