<?php

namespace App\Tests;

use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\User;
use App\Enum\CollectionItemStates;
use App\Enum\Currency;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CollectorControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }

    public function testAddCollectionItemUnauthenticated(): void
    {
        $this->client->request('GET', '/collector/addCollectionItem');
        self::assertResponseRedirects('/login');
    }

    public function testAddCollectionItemUnauthorized(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('regular@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/collector/addCollectionItem');
        self::assertResponseStatusCodeSame(403);
    }

    public function testAddCollectionItemSuccess(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('collector@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_COLLECTOR']);
        $game = (new Game())
            ->setTitle('Super Mario Bros.')
            ->setConsole('NES')
            ->setReleaseYear(1985)
            ->setIsHidden(false);

        $this->entityManager->persist($user);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/collector/addCollectionItem');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Ajouter un jeu à ma collection');

        $form = $crawler->selectButton('Ajouter')->form([
            'collection_item[game]' => $game->getId(),
            'collection_item[state]' => CollectionItemStates::MINT->value,
            'collection_item[acquisitionPrice]' => 45,
            'collection_item[currency]' => Currency::EUR->value,
            'collection_item[acquisitionDate]' => '2026-05-29',
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects('/collector/myCollection');
        $this->client->followRedirect();

        $collectionItemRepository = $this->entityManager->getRepository(CollectionItem::class);
        $items = $collectionItemRepository->findAll();
        self::assertCount(1, $items);
        self::assertEquals($game->getId(), $items[0]->getGame()->getId());
        self::assertEquals($user->getId(), $items[0]->getCollector()->getId());
        self::assertEquals(CollectionItemStates::MINT, $items[0]->getState());
        self::assertEquals(4500, $items[0]->getAcquisitionPrice());
        self::assertEquals(Currency::EUR, $items[0]->getCurrency());
        self::assertEquals('2026-05-29', $items[0]->getAcquisitionDate()->format('Y-m-d'));
    }

    public function testAddCollectionItemValidationError(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('collector_validation@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/collector/addCollectionItem');
        self::assertResponseIsSuccessful();

        $collectionItemRepository = $this->entityManager->getRepository(CollectionItem::class);
        $initialCount = count($collectionItemRepository->findAll());

        $form = $crawler->selectButton('Ajouter')->form();
        $values = $form->getPhpValues();

        $values['collection_item']['game'] = '';
        $values['collection_item']['state'] = '';
        $values['collection_item']['acquisitionPrice'] = -10;
        $values['collection_item']['currency'] = '';

        $this->client->request($form->getMethod(), $form->getUri(), $values);

        self::assertSelectorTextContains('body', 'Veuillez sélectionner un jeu.');
        self::assertSelectorTextContains('body', 'Veuillez sélectionner un état.');
        self::assertSelectorTextContains('body', 'Le prix d\'acquisition doit être strictement supérieur à 0.');
        self::assertSelectorTextContains('body', 'Veuillez sélectionner une devise.');

        self::assertCount($initialCount, $collectionItemRepository->findAll());

        //CAS DATE : 
        $crawler = $this->client->request('GET', '/collector/addCollectionItem');
        self::assertResponseIsSuccessful();
        $collectionItemRepository = $this->entityManager->getRepository(CollectionItem::class);
        $initialCount = count($collectionItemRepository->findAll());

        $form = $crawler->selectButton('Ajouter')->form();
        $values = $form->getPhpValues();

        $values['collection_item']['acquisitionDate'] = '';

        $this->client->request($form->getMethod(), $form->getUri(), $values);
        self::assertResponseStatusCodeSame(302);
        self::assertResponseRedirects('/collector/addCollectionItem');
        self::assertCount($initialCount, $collectionItemRepository->findAll());
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Une erreur est survenue lors de l\'ajout du jeu à votre collection.');
    }

    public function testDeleteCollectionItemUnauthenticated(): void
    {
        $this->client->request('POST', '/collector/deleteCollectionItem/1');
        self::assertResponseRedirects('/login');
    }

    public function testDeleteCollectionItemUnauthorized(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('regular_delete@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('POST', '/collector/deleteCollectionItem/1');
        self::assertResponseStatusCodeSame(403);
    }

    public function testDeleteCollectionItemInvalidCsrf(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('collector_csrf@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_COLLECTOR']);

        $game = (new Game())
            ->setTitle('Sonic the Hedgehog')
            ->setConsole('Mega Drive')
            ->setReleaseYear(1991)
            ->setIsHidden(false);

        $collectionItem = (new CollectionItem())
            ->setCollector($user)
            ->setGame($game)
            ->setState(CollectionItemStates::GOOD)
            ->setAcquisitionPrice(2000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->persist($game);
        $this->entityManager->persist($collectionItem);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('POST', '/collector/deleteCollectionItem/' . $collectionItem->getId(), [
            '_token' => 'invalid_csrf_token'
        ]);

        self::assertResponseRedirects('/collector/myCollection');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Le jeton de sécurité est invalide. Veuillez réessayer.');

        // Verify entity still exists
        $item = $this->entityManager->getRepository(CollectionItem::class)->find($collectionItem->getId());
        self::assertNotNull($item);
    }

    public function testDeleteCollectionItemNotOwner(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $owner = (new User())->setEmail('owner@example.com');
        $owner->setPassword($passwordHasher->hashPassword($owner, 'password'));
        $owner->setRoles(['ROLE_COLLECTOR']);

        $other = (new User())->setEmail('other@example.com');
        $other->setPassword($passwordHasher->hashPassword($other, 'password'));
        $other->setRoles(['ROLE_COLLECTOR']);

        $game = (new Game())
            ->setTitle('Tetris')
            ->setConsole('Game Boy')
            ->setReleaseYear(1989)
            ->setIsHidden(false);

        $collectionItem = (new CollectionItem())
            ->setCollector($owner)
            ->setGame($game)
            ->setState(CollectionItemStates::GOOD)
            ->setAcquisitionPrice(1000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($owner);
        $this->entityManager->persist($other);
        $this->entityManager->persist($game);
        $this->entityManager->persist($collectionItem);
        $this->entityManager->flush();

        // Log in as the non-owner user
        $this->client->loginUser($other);
        $this->client->request('POST', '/collector/deleteCollectionItem/' . $collectionItem->getId());
        
        self::assertResponseStatusCodeSame(403);

        // Verify entity still exists
        $item = $this->entityManager->getRepository(CollectionItem::class)->find($collectionItem->getId());
        self::assertNotNull($item);
    }

    public function testDeleteCollectionItemSuccess(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('collector_delete_success@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_COLLECTOR']);

        $game = (new Game())
            ->setTitle('Zelda: Link\'s Awakening')
            ->setConsole('Game Boy')
            ->setReleaseYear(1993)
            ->setIsHidden(false);

        $collectionItem = (new CollectionItem())
            ->setCollector($user)
            ->setGame($game)
            ->setState(CollectionItemStates::MINT)
            ->setAcquisitionPrice(6000)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->persist($game);
        $this->entityManager->persist($collectionItem);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/collector/myCollection');
        $html = $this->client->getResponse()->getContent();
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);
        $div = $crawler->filter('[data-symfony--ux-vue--vue-component-value="MyCollection"]');
        $props = json_decode($div->attr('data-symfony--ux-vue--vue-props-value'), true);
        $token = $props['csrfToken'];

        $this->client->request('POST', '/collector/deleteCollectionItem/' . $collectionItem->getId(), [
            '_token' => $token
        ]);

        self::assertResponseRedirects('/collector/myCollection');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Le jeu a été retiré de votre collection.');

        $item = $this->entityManager->getRepository(CollectionItem::class)->find($collectionItem->getId());
        self::assertNull($item);
    }

    public function testShowGameSuccess(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('collector_show@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_COLLECTOR']);
        
        $game = (new Game())
            ->setTitle('Super Mario Land')
            ->setConsole('Game Boy')
            ->setReleaseYear(1989)
            ->setIsHidden(false);

        $this->entityManager->persist($user);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/collector/game/' . $game->getId());
        
        self::assertResponseIsSuccessful();
        
        $html = $this->client->getResponse()->getContent();
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);
        $div = $crawler->filter('[data-symfony--ux-vue--vue-component-value="GameShow"]');
        self::assertCount(1, $div);
        
        $props = json_decode($div->attr('data-symfony--ux-vue--vue-props-value'), true);
        self::assertEquals('Super Mario Land', $props['game']['title']);
        self::assertEquals('Game Boy', $props['game']['console']);
        self::assertEquals(1989, $props['game']['releaseYear']);
        self::assertArrayHasKey('description', $props);
        self::assertArrayHasKey('collectionCount', $props);
        self::assertArrayHasKey('averagePrices', $props);
    }
}

