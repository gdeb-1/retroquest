<?php

namespace App\Controller\Collector;

use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLECTOR')]
class GuildCatalogController extends AbstractController
{
    #[Route('/collector/GuildCatalog', name: 'app_collector_guild_catalog', options: ['expose' => true])]
    public function guildCatalog(GameRepository $gameRepository): Response
    {
        $games = $gameRepository->findBy(['isHidden' => false]);

        $catalogData = [];
        foreach ($games as $game) {
            $catalogData[] = [
                'id' => $game->getId(),
                'title' => $game->getTitle(),
                'console' => $game->getConsole(),
                'releaseYear' => $game->getReleaseYear(),
            ];
        }

        return $this->render('collector/guild_catalog.html.twig', [
            'catalogData' => $catalogData,
        ]);
    }
}
