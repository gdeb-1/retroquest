<?php

namespace App\Tests\Controller\Collector\Game;

use App\Entity\Game;
use App\Entity\Review;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ShowGameControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
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

        $validatedReview = (new Review())
            ->setComment('Awesome game!')
            ->setIsValid(true)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setAuthor($user)
            ->setGame($game);

        $unvalidatedReview = (new Review())
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
        $crawler = new Crawler($html);
        $div = $crawler->filter('[data-symfony--ux-vue--vue-component-value="GameShow"]');
        self::assertCount(1, $div);
        
        $props = json_decode($div->attr('data-symfony--ux-vue--vue-props-value'), true);
        self::assertEquals('Super Mario Land', $props['game']['title']);
        self::assertEquals('Game Boy', $props['game']['console']);
        self::assertEquals(1989, $props['game']['releaseYear']);
        self::assertArrayHasKey('description', $props);
        self::assertArrayHasKey('collectionCount', $props);
        self::assertArrayHasKey('averagePrices', $props);
        
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

        $reviewRepository = $this->entityManager->getRepository(Review::class);
        $reviews = $reviewRepository->findBy(['game' => $game, 'author' => $user]);
        self::assertCount(1, $reviews);
        self::assertEquals('This is an awesome game review!', $reviews[0]->getComment());
        self::assertFalse($reviews[0]->isValid());

        self::assertSelectorTextContains('body', 'Votre avis a été soumis avec succès et est en attente de modération.');

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

        $crawler = $this->client->request('POST', '/collector/game/' . $game->getId(), [
            'review' => [
                'comment' => '',
                '_token' => $csrfToken
            ]
        ]);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Votre avis ne peut pas être vide.');

        $crawler = $this->client->request('POST', '/collector/game/' . $game->getId(), [
            'review' => [
                'comment' => 'Wow',
                '_token' => $csrfToken
            ]
        ]);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Votre avis doit contenir au moins 5 caractères.');
        self::assertEquals('Wow', $crawler->filter('textarea[name="review[comment]"]')->text());

        $reviewRepository = $this->entityManager->getRepository(Review::class);
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

        $existingReview = (new Review())
            ->setComment('Initial comment')
            ->setIsValid(false)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setAuthor($user)
            ->setGame($game);

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
        
        self::assertCount(0, $crawler->filter('input[name="review[_token]"]'));

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

        $reviewRepository = $this->entityManager->getRepository(Review::class);
        $reviews = $reviewRepository->findBy(['game' => $game, 'author' => $user]);
        self::assertCount(1, $reviews);
        self::assertEquals('Initial comment', $reviews[0]->getComment());
    }
}
