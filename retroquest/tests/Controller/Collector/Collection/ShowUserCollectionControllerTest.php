<?php

namespace App\Tests\Controller\Collector\Collection;

use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\User;
use App\Enum\CollectionItemStates;
use App\Enum\Currency;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ShowUserCollectionControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }

    public function testMyCollectionUnauthenticated(): void
    {
        $this->client->request('GET', '/collector/myCollection');
        self::assertResponseRedirects('/login');
    }

    public function testMyCollectionUnauthorized(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('regular_collector@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/collector/myCollection');
        self::assertResponseStatusCodeSame(403);
    }

    public function testMyCollectionSuccess(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        // Create collector
        $user = (new User())->setEmail('real_collector@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_COLLECTOR']);

        // Create a game
        $game = (new Game())
            ->setTitle('Sonic the Hedgehog')
            ->setConsole('Mega Drive')
            ->setReleaseYear(1991)
            ->setIsHidden(false);

        // Add item to collection
        $item = (new CollectionItem())
            ->setCollector($user)
            ->setGame($game)
            ->setState(CollectionItemStates::GOOD)
            ->setCurrency(Currency::EUR)
            ->setAcquisitionPrice(1500) // 15.00 EUR
            ->setAcquisitionDate(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->persist($game);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/collector/myCollection');
        self::assertResponseIsSuccessful();

        // Verify the HTML response contains the vue component with collectionItemsData and estimations
        $responseContent = $this->client->getResponse()->getContent();
        self::assertStringContainsString('pages/MyCollection', $responseContent);
        self::assertStringContainsString('estimations', $responseContent);
        self::assertStringContainsString('1500', $responseContent);
    }
}
