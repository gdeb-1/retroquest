<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CollectionItemRepository;
use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLECTOR')]
class CollectorController extends AbstractController
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

    #[Route('/collector/myCollection', name: 'app_collector_my_collection', options: ['expose' => true])]
    public function myCollection(CollectionItemRepository $collectionItemRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $collectionItems = $collectionItemRepository->findByCollectorWithGame($user);

        $collectionItemsData = [];
        foreach ($collectionItems as $item) {
            $game = $item->getGame();
            $collectionItemsData[] = [
                'id' => $item->getId(),
                'state' => $item->getState()->value,
                'acquisitionPrice' => $item->getAcquisitionPrice(),
                'currency' => $item->getCurrency()->value,
                'game' => [
                    'id' => $game->getId(),
                    'title' => $game->getTitle(),
                    'console' => $game->getConsole(),
                    'releaseYear' => $game->getReleaseYear(),
                ],
            ];
        }

        return $this->render('collector/my_collection.html.twig', [
            'collectionItemsData' => $collectionItemsData,
        ]);
    }
}
