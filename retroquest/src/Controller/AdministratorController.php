<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\CollectionItem;
use App\Form\CollectionItemType;
use App\Repository\CollectionItemRepository;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdministratorController extends AbstractController
{
    #[Route('/administrator/helloAdministrator', name: 'app_administrator_hello_administrator', options: ['expose' => true])]
    public function helloAdministrator(): Response
    {
        return $this->render('administrator/hello_administrator.html.twig');
    }

    #[Route('/administrator/users', name: 'app_administrator_users', options: ['expose' => true])]
    public function users(EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('administrator/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/administrator/users/{id}/edit', name: 'app_administrator_edit_user')]
    public function editUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(\App\Form\UserRoleType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Le role de l\'utilisateur a bien été mis a jour.');

            return $this->redirectToRoute('app_administrator_users');
        }

        return $this->render('administrator/edit_user.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}