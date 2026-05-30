<?php

namespace App\Tests;

use App\Entity\Review;
use App\Entity\Game;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ModeratorControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }

    private function createModeratorUser(): User
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('moderator_test_review@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_MODERATOR']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createCollectorUser(): User
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('collector_test_review@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_COLLECTOR']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createGameAndReview(User $author, bool $isValid = false): Review
    {
        $game = (new Game())
            ->setTitle('Metroid Prime')
            ->setConsole('GameCube')
            ->setReleaseYear(2002)
            ->setIsHidden(false);

        $review = (new Review())
            ->setComment('Awesome sci-fi atmospheric adventure.')
            ->setIsValid($isValid)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setGame($game)
            ->setAuthor($author);

        $this->entityManager->persist($game);
        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $review;
    }

    public function testReviewsPageUnauthenticated(): void
    {
        $this->client->request('GET', '/moderator/review');
        self::assertResponseRedirects('/login');
    }

    public function testReviewsPageUnauthorized(): void
    {
        $user = $this->createCollectorUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/moderator/review');
        self::assertResponseStatusCodeSame(403);
    }

    public function testReviewsPageSuccess(): void
    {
        $mod = $this->createModeratorUser();
        $collector = $this->createCollectorUser();
        $review = $this->createGameAndReview($collector);

        $this->client->loginUser($mod);
        $this->client->request('GET', '/moderator/review');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-symfony--ux-vue--vue-component-value="ModeratorReviews"]');
    }
}
