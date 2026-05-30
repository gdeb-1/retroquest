<?php

namespace App\Controller\Moderator;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MODERATOR')]
class HelloModeratorController extends AbstractController
{
    #[Route('/moderator/helloModerator', name: 'app_moderator_hello_moderator', options: ['expose' => true])]
    public function helloModerator(): Response
    {
        return $this->render('moderator/hello_moderator.html.twig');
    }
}
