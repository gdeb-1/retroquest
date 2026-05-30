<?php

namespace App\Controller\Administrator;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class HelloAdministratorController extends AbstractController
{
    #[Route('/administrator/helloAdministrator', name: 'app_administrator_hello_administrator', options: ['expose' => true])]
    public function helloAdministrator(): Response
    {
        return $this->render('administrator/hello_administrator.html.twig');
    }
}
