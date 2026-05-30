<?php

namespace App\Controller\Administrator\User;

use App\Entity\User;
use App\Form\UserRoleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class EditUserController extends AbstractController
{
    #[Route('/administrator/users/{id}/edit', name: 'app_administrator_edit_user')]
    public function editUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserRoleType::class, $user);
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
