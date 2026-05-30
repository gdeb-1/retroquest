<?php

namespace App\Tests\Controller\Collector\Collection;

use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\User;
use App\Enum\CollectionItemStates;
use App\Enum\Currency;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class DeleteCollectionItemControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
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

        $this->client->loginUser($other);
        $this->client->request('POST', '/collector/deleteCollectionItem/' . $collectionItem->getId());
        
        self::assertResponseStatusCodeSame(403);

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
        $crawler = new Crawler($html);
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
}
