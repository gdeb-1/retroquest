<?php

namespace App\Tests\Controller\Collector\Collection;

use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\User;
use App\Enum\CollectionItemStates;
use App\Enum\Currency;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Tests\DatabaseWebTestCase;

class AddCollectionItemControllerTest extends DatabaseWebTestCase
{


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
}
