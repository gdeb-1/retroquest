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
        $review = $this->createGameAndReview($this->collector);

        $this->client->loginUser($this->moderator);
        $this->client->request('GET', '/moderator/review');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-symfony--ux-vue--vue-component-value="ModeratorReviews"]');
    }

    public function testValidateReviewSuccess(): void
    {
        $review = $this->createGameAndReview($this->collector, false);

        $this->client->loginUser($this->moderator);
        $this->client->request('GET', '/moderator/review');
        self::assertResponseIsSuccessful();

        // Extract CSRF token from Vue component props
        $html = $this->client->getResponse()->getContent();
        $crawler = new Crawler($html);
        $div = $crawler->filter('[data-symfony--ux-vue--vue-component-value="ModeratorReviews"]');
        $props = json_decode($div->attr('data-symfony--ux-vue--vue-props-value'), true);
        
        $token = null;
        foreach ($props['reviews'] as $r) {
            if ($r['id'] === $review->getId()) {
                $token = $r['csrfTokenValidate'];
                break;
            }
        }
        self::assertNotNull($token);

        // Perform validation
        $this->client->request('POST', '/moderator/review/validate/' . $review->getId(), [
            '_token' => $token
        ]);

        self::assertResponseRedirects('/moderator/review');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'L\'avis a été validé avec succès.');

        // Verify state in DB
        $this->entityManager->clear();
        $updatedReview = $this->entityManager->getRepository(Review::class)->find($review->getId());
        self::assertTrue($updatedReview->isValid());
    }

    public function testValidateReviewInvalidCsrf(): void
    {
        $review = $this->createGameAndReview($this->collector, false);

        $this->client->loginUser($this->moderator);
        $this->client->request('POST', '/moderator/review/validate/' . $review->getId(), [
            '_token' => 'invalid_csrf_token'
        ]);

        self::assertResponseRedirects('/moderator/review');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Le jeton de sécurité est invalide.');

        // Verify state in DB unchanged
        $this->entityManager->clear();
        $updatedReview = $this->entityManager->getRepository(Review::class)->find($review->getId());
        self::assertFalse($updatedReview->isValid());
    }

    public function testDeleteReviewSuccess(): void
    {
        $review = $this->createGameAndReview($this->collector, false);

        $this->client->loginUser($this->moderator);
        $this->client->request('GET', '/moderator/review');
        self::assertResponseIsSuccessful();

        // Extract CSRF token from Vue component props
        $html = $this->client->getResponse()->getContent();
        $crawler = new Crawler($html);
        $div = $crawler->filter('[data-symfony--ux-vue--vue-component-value="ModeratorReviews"]');
        $props = json_decode($div->attr('data-symfony--ux-vue--vue-props-value'), true);
        
        $token = null;
        foreach ($props['reviews'] as $r) {
            if ($r['id'] === $review->getId()) {
                $token = $r['csrfTokenDelete'];
                break;
            }
        }
        self::assertNotNull($token);

        // Perform deletion
        $this->client->request('POST', '/moderator/review/delete/' . $review->getId(), [
            '_token' => $token
        ]);

        self::assertResponseRedirects('/moderator/review');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'L\'avis a été supprimé avec succès.');

        // Verify deleted from DB
        $this->entityManager->clear();
        $deletedReview = $this->entityManager->getRepository(Review::class)->find($review->getId());
        self::assertNull($deletedReview);
    }

    public function testDeleteReviewInvalidCsrf(): void
    {
        $review = $this->createGameAndReview($this->collector, false);

        $this->client->loginUser($this->moderator);
        $this->client->request('POST', '/moderator/review/delete/' . $review->getId(), [
            '_token' => 'invalid_csrf_token'
        ]);

        self::assertResponseRedirects('/moderator/review');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Le jeton de sécurité est invalide.');

        // Verify state in DB unchanged
        $this->entityManager->clear();
        $updatedReview = $this->entityManager->getRepository(Review::class)->find($review->getId());
        self::assertNotNull($updatedReview);
    }
}
