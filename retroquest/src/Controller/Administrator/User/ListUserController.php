<?php

namespace App\Controller\Administrator\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ListUserController extends AbstractController
{
    #[Route('/administrator/users', name: 'app_administrator_users', options: ['expose' => true])]
    public function users(EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('administrator/users.html.twig', [
            'users' => $users,
        ]);
    }
}
