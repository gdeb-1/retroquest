<?php

namespace App\Controller\Moderator\Game;

use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MODERATOR')]
class ListGameController extends AbstractController
{
    #[Route('/moderator/games', name: 'app_moderator_games', options: ['expose' => true])]
    public function games(GameRepository $gameRepository): Response
    {
        $games = $gameRepository->findAll();

        return $this->render('moderator/games.html.twig', [
            'games' => $games,
        ]);
    }
}
