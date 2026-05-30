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

        $validatedReview = (new \App\Entity\Review())
            ->setComment('Awesome game!')
            ->setIsValid(true)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setAuthor($user)
            ->setGame($game);

        $unvalidatedReview = (new \App\Entity\Review())
            ->setComment('Pending moderation...')
            ->setIsValid(false)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setAuthor($user)
            ->setGame($game);

        $this->entityManager->persist($user);
        $this->entityManager->persist($game);
        $this->entityManager->persist($validatedReview);
        $this->entityManager->persist($unvalidatedReview);
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
        
        // Assert reviews
        self::assertArrayHasKey('reviews', $props);
        self::assertCount(1, $props['reviews']);
        self::assertEquals('Awesome game!', $props['reviews'][0]['comment']);
        self::assertEquals('collector_show@example.com', $props['reviews'][0]['authorEmail']);
    }

    public function testLeaveReviewUnauthenticated(): void
    {
        $game = (new Game())
            ->setTitle('Test Game Unauthenticated')
            ->setConsole('NES')
            ->setReleaseYear(1985)
            ->setIsHidden(false);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $this->client->request('POST', '/collector/game/' . $game->getId(), [
            'review' => [
                'comment' => 'This is a test review by guest.',
                '_token' => 'some_token'
            ]
        ]);
        self::assertResponseRedirects('/login');
    }

    public function testLeaveReviewUnauthorized(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('regular_review@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);

        $game = (new Game())
            ->setTitle('Test Game Unauthorized')
            ->setConsole('NES')
            ->setReleaseYear(1985)
            ->setIsHidden(false);

        $this->entityManager->persist($user);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('POST', '/collector/game/' . $game->getId(), [
            'review' => [
                'comment' => 'This is a test review.',
                '_token' => 'some_token'
            ]
        ]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testLeaveReviewSuccess(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('collector_review_success@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_COLLECTOR']);

        $game = (new Game())
            ->setTitle('Test Game Success')
            ->setConsole('NES')
            ->setReleaseYear(1985)
            ->setIsHidden(false);

        $this->entityManager->persist($user);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        
        $crawler = $this->client->request('GET', '/collector/game/' . $game->getId());
        self::assertResponseIsSuccessful();

        // Extract CSRF token from the Twig-rendered form input
        $csrfToken = $crawler->filter('input[name="review[_token]"]')->attr('value');
        self::assertNotEmpty($csrfToken);

        $this->client->request('POST', '/collector/game/' . $game->getId(), [
            'review' => [
                'comment' => 'This is an awesome game review!',
                '_token' => $csrfToken
            ]
        ]);

        self::assertResponseRedirects('/collector/game/' . $game->getId());
        $this->client->followRedirect();

        $reviewRepository = $this->entityManager->getRepository(\App\Entity\Review::class);
        $reviews = $reviewRepository->findBy(['game' => $game, 'author' => $user]);
        self::assertCount(1, $reviews);
        self::assertEquals('This is an awesome game review!', $reviews[0]->getComment());
        self::assertFalse($reviews[0]->isValid());

        self::assertSelectorTextContains('body', 'Votre avis a été soumis avec succès et est en attente de modération.');

        // Verify the form is no longer rendered
        $crawler = $this->client->getCrawler();
        self::assertCount(0, $crawler->filter('input[name="review[_token]"]'));
    }

    public function testLeaveReviewValidationError(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('collector_review_validation@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_COLLECTOR']);

        $game = (new Game())
            ->setTitle('Test Game Validation')
            ->setConsole('NES')
            ->setReleaseYear(1985)
            ->setIsHidden(false);

        $this->entityManager->persist($user);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        
        $crawler = $this->client->request('GET', '/collector/game/' . $game->getId());
        $csrfToken = $crawler->filter('input[name="review[_token]"]')->attr('value');

        // Submit empty comment
        $crawler = $this->client->request('POST', '/collector/game/' . $game->getId(), [
            'review' => [
                'comment' => '',
                '_token' => $csrfToken
            ]
        ]);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Votre avis ne peut pas être vide.');

        // Submit too short comment
        $crawler = $this->client->request('POST', '/collector/game/' . $game->getId(), [
            'review' => [
                'comment' => 'Wow',
                '_token' => $csrfToken
            ]
        ]);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Votre avis doit contenir au moins 5 caractères.');
        self::assertEquals('Wow', $crawler->filter('textarea[name="review[comment]"]')->text());

        $reviewRepository = $this->entityManager->getRepository(\App\Entity\Review::class);
        $reviews = $reviewRepository->findBy(['game' => $game, 'author' => $user]);
        self::assertCount(0, $reviews);
    }

    public function testLeaveReviewDuplicate(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('collector_review_duplicate@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_COLLECTOR']);

        $game = (new Game())
            ->setTitle('Test Game Duplicate')
            ->setConsole('NES')
            ->setReleaseYear(1985)
            ->setIsHidden(false);

        $existingReview = (new \App\Entity\Review())
            ->setComment('Initial comment')
            ->setIsValid(false)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setAuthor($user)
            ->setGame($game);

        // Create a second game that the user has NOT reviewed to fetch a valid CSRF token from
        $game2 = (new Game())
            ->setTitle('Test Game 2')
            ->setConsole('NES')
            ->setReleaseYear(1985)
            ->setIsHidden(false);

        $this->entityManager->persist($user);
        $this->entityManager->persist($game);
        $this->entityManager->persist($game2);
        $this->entityManager->persist($existingReview);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        
        $crawler = $this->client->request('GET', '/collector/game/' . $game->getId());
        self::assertResponseIsSuccessful();
        
        // Form should not be rendered
        self::assertCount(0, $crawler->filter('input[name="review[_token]"]'));

        // GET the page of the second game to retrieve a valid CSRF token
        $crawler2 = $this->client->request('GET', '/collector/game/' . $game2->getId());
        self::assertResponseIsSuccessful();
        $csrfToken = $crawler2->filter('input[name="review[_token]"]')->attr('value');

        $this->client->request('POST', '/collector/game/' . $game->getId(), [
            'review' => [
                'comment' => 'Another comment',
                '_token' => $csrfToken
            ]
        ]);

        self::assertResponseRedirects('/collector/game/' . $game->getId());
        $this->client->followRedirect();

        self::assertSelectorTextContains('body', 'Vous avez déjà laissé un avis sur ce jeu.');

        $reviewRepository = $this->entityManager->getRepository(\App\Entity\Review::class);
        $reviews = $reviewRepository->findBy(['game' => $game, 'author' => $user]);
        self::assertCount(1, $reviews);
        self::assertEquals('Initial comment', $reviews[0]->getComment());
    }
}

