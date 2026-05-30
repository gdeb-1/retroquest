<?php

namespace App\Tests\Controller\Moderator\Review;

use App\Entity\Game;
use App\Entity\Review;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ListReviewControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private $entityManager;
    private User $moderator;
    private User $collector;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        $this->moderator = $this->createModeratorUser();
        $this->collector = $this->createCollectorUser();
    }

    private function createModeratorUser(): User
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $email = 'moderator_' . uniqid('', true) . '@example.com';
        $user = (new User())->setEmail($email);
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

        $email = 'collector_' . uniqid('', true) . '@example.com';
        $user = (new User())->setEmail($email);
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
        $this->client->loginUser($this->collector);

        $this->client->request('GET', '/moderator/review');
        self::assertResponseStatusCodeSame(403);
    }

    public function testReviewsPageSuccess(): void
    {
        $this->createGameAndReview($this->collector);

        $this->client->loginUser($this->moderator);
        $this->client->request('GET', '/moderator/review');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-symfony--ux-vue--vue-component-value="ModeratorReviews"]');
    }
}
