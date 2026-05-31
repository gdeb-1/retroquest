<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    private static ?array $cachedTables = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        
        $container = static::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;

        $this->truncateDatabase();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $this->entityManager->close();
            unset($this->entityManager);
        }
        
        unset($this->client);

        parent::tearDown();
    }

    private function truncateDatabase(): void
    {
        if (!isset($this->entityManager)) {
            return;
        }

        $connection = $this->entityManager->getConnection();

        if (self::$cachedTables === null) {
            $schemaManager = $connection->createSchemaManager();
            self::$cachedTables = $schemaManager->listTableNames();
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach (self::$cachedTables as $table) {
            if (str_contains(strtolower($table), 'migration')) {
                continue;
            }
            $connection->executeStatement(sprintf('DELETE FROM `%s`', $table));
        }
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
