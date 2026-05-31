<?php

namespace App\Tests\Controller\Registration;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegisterAssignsCollectorRole(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton("S'inscrire")->form([
            'registration_form[email]' => 'new_collector@example.com',
            'registration_form[plainPassword]' => 'SecurePassword123!',
            'registration_form[agreeTerms]' => true,
        ]);

        $client->submit($form);

        // Check if redirected or successful registration
        self::assertResponseRedirects();

        // Check database
        $container = static::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'new_collector@example.com']);

        self::assertNotNull($user);
        self::assertContains('ROLE_COLLECTOR', $user->getRoles());
    }
}
