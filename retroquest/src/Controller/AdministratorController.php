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
}