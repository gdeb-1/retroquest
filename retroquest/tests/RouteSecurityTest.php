<?php

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RouteSecurityTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }

    private function createUserWithRoles(string $email, array $roles): User
    {
        $container = static::getContainer();
        $passwordHasher = $container->get('security.user_password_hasher');

        $uniqueEmail = str_replace('@', '_' . uniqid('', true) . '@', $email);

        $user = (new User())->setEmail($uniqueEmail);
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Test that unauthenticated users are redirected to the login page for protected routes.
     */
    #[DataProvider('provideProtectedRoutes')]
    public function testUnauthenticatedRedirectsToLogin(string $route): void
    {
        $this->client->request('GET', $route);
        self::assertResponseRedirects('/login');
    }

    public static function provideProtectedRoutes(): array
    {
        return [
            ['/administrator/helloAdministrator'],
            ['/moderator/helloModerator'],
            ['/collector/myCollection'],
            ['/collector/GuildCatalog'],
            ['/collector/addCollectionItem'],
            ['/collector/game/1'],
        ];
    }

    /**
     * Test access controls for the Administrator route.
     */
    #[DataProvider('provideAdminRouteAccessCases')]
    public function testAdministratorRouteAccess(array $roles, int $expectedStatus): void
    {
        $user = $this->createUserWithRoles('admin_test@example.com', $roles);
        $this->client->loginUser($user);

        $this->client->request('GET', '/administrator/helloAdministrator');
        self::assertResponseStatusCodeSame($expectedStatus);
    }

    public static function provideAdminRouteAccessCases(): array
    {
        return [
            [['ROLE_USER'], 403],
            [['ROLE_COLLECTOR'], 403],
            [['ROLE_MODERATOR'], 403],
            [['ROLE_ADMIN'], 200],
        ];
    }

    /**
     * Test access controls for the Moderator route.
     */
    #[DataProvider('provideModeratorRouteAccessCases')]
    public function testModeratorRouteAccess(array $roles, int $expectedStatus): void
    {
        $user = $this->createUserWithRoles('moderator_test@example.com', $roles);
        $this->client->loginUser($user);

        $this->client->request('GET', '/moderator/helloModerator');
        self::assertResponseStatusCodeSame($expectedStatus);
    }

    public static function provideModeratorRouteAccessCases(): array
    {
        return [
            [['ROLE_USER'], 403],
            [['ROLE_COLLECTOR'], 403],
            [['ROLE_ADMIN'], 200],
            [['ROLE_MODERATOR'], 200],
        ];
    }

    /**
     * Test access controls for the Collector route.
     */
    #[DataProvider('provideCollectorRouteAccessCases')]
    public function testCollectorRouteAccess(array $roles, int $expectedStatus): void
    {
        $user = $this->createUserWithRoles('collector_test@example.com', $roles);
        $this->client->loginUser($user);

        $this->client->request('GET', '/collector/myCollection');
        self::assertResponseStatusCodeSame($expectedStatus);
    }

    public static function provideCollectorRouteAccessCases(): array
    {
        return [
            [['ROLE_USER'], 403],
            [['ROLE_MODERATOR'], 200],
            [['ROLE_ADMIN'], 200],
            [['ROLE_COLLECTOR'], 200],
        ];
    }
}
