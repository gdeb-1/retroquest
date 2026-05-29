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

#[IsGranted('ROLE_MODERATOR')]
class ModeratorController extends AbstractController
{
    #[Route('/moderator/helloModerator', name: 'app_moderator_hello_moderator', options: ['expose' => true])]
    public function helloModerator(): Response
    {
        return $this->render('moderator/hello_moderator.html.twig');
    }

    #[Route('/moderator/games', name: 'app_moderator_games', options: ['expose' => true])]
    public function games(GameRepository $gameRepository): Response
    {
        $games = $gameRepository->findAll();

        return $this->render('moderator/games.html.twig', [
            'games' => $games,
        ]);
    }
}