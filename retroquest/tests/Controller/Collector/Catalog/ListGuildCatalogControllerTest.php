<?php

namespace App\Tests\Controller\Collector\Catalog;

use App\Entity\Game;
use App\Entity\User;
use App\Tests\DatabaseWebTestCase;

class ListGuildCatalogControllerTest extends DatabaseWebTestCase
{
    public function testGuildCatalogUnauthenticated(): void
    {
        $this->client->request('GET', '/collector/GuildCatalog');
        self::assertResponseRedirects('/login');
    }

    public function testGuildCatalogDataUnauthenticated(): void
    {
        $this->client->request('GET', '/collector/GuildCatalog/data');
        self::assertResponseRedirects('/login');
    }

    public function testGuildCatalogDataUnauthorized(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('non_collector@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/collector/GuildCatalog/data');
        self::assertResponseStatusCodeSame(403);
    }

    public function testGuildCatalogDataSuccess(): void
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        // Create collector
        $user = (new User())->setEmail('collector@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_COLLECTOR']);

        // Create games
        $game1 = (new Game())
            ->setTitle('Zelda: A Link to the Past')
            ->setConsole('Super Nintendo')
            ->setReleaseYear(1991)
            ->setIsHidden(false);

        $game2 = (new Game())
            ->setTitle('Super Mario World')
            ->setConsole('Super Nintendo')
            ->setReleaseYear(1990)
            ->setIsHidden(false);

        // Hidden game
        $game3 = (new Game())
            ->setTitle('Secret Game')
            ->setConsole('NES')
            ->setReleaseYear(1988)
            ->setIsHidden(true);

        $this->entityManager->persist($user);
        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);
        $this->entityManager->persist($game3);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        // 1. Basic pagination test (default GET without query params)
        $this->client->request('GET', '/collector/GuildCatalog/data');
        self::assertResponseIsSuccessful();
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);

        self::assertArrayHasKey('draw', $responseContent);
        self::assertArrayHasKey('recordsTotal', $responseContent);
        self::assertArrayHasKey('recordsFiltered', $responseContent);
        self::assertArrayHasKey('data', $responseContent);

        // We have 2 visible games total
        self::assertSame(2, $responseContent['recordsTotal']);
        self::assertSame(2, $responseContent['recordsFiltered']);
        self::assertCount(2, $responseContent['data']);

        // 2. Search test
        $this->client->request('GET', '/collector/GuildCatalog/data', [
            'search' => ['value' => 'Zelda']
        ]);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame(1, $responseContent['recordsFiltered']);
        self::assertSame('Zelda: A Link to the Past', $responseContent['data'][0]['title']);

        // 3. Sorting test (Sort by releaseYear descending)
        $this->client->request('GET', '/collector/GuildCatalog/data', [
            'columns' => [
                0 => ['data' => 'title'],
                1 => ['data' => 'console'],
                2 => ['data' => 'releaseYear']
            ],
            'order' => [
                0 => ['column' => 2, 'dir' => 'desc']
            ]
        ]);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        // Zelda (1991) should be first, Super Mario (1990) second
        self::assertSame('Zelda: A Link to the Past', $responseContent['data'][0]['title']);

        // Sort by releaseYear ascending
        $this->client->request('GET', '/collector/GuildCatalog/data', [
            'columns' => [
                0 => ['data' => 'title'],
                1 => ['data' => 'console'],
                2 => ['data' => 'releaseYear']
            ],
            'order' => [
                0 => ['column' => 2, 'dir' => 'asc']
            ]
        ]);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        // Super Mario (1990) should be first
        self::assertSame('Super Mario World', $responseContent['data'][0]['title']);
    }
}
