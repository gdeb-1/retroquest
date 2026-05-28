<?php

namespace App\Controller;

use App\Repository\GameRepository;
use App\Repository\CollectionItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(GameRepository $gameRepository, CollectionItemRepository $collectionItemRepository): Response
    {
        //TODO : strong typing (typed Collection)
        $popularGamesRaw = $gameRepository->findPopularGames(30, 12);
        $averagePricesByGame = $this->retrieveAveragePricesByGames($popularGamesRaw, $collectionItemRepository);
        $popularGames = $this->mapRowToGame($popularGamesRaw, $averagePricesByGame);

        return $this->render('home/index.html.twig', [
            'popularGames' => $popularGames,
        ]);
    }

    private function retrieveAveragePricesByGames(array $popularGamesRaw, CollectionItemRepository $collectionItemRepository): array
    {
        $games = array_map(fn($row) => $row[0], $popularGamesRaw);
        $averagePricesRaw = $collectionItemRepository->findAveragePricesForGames($games);

        $averagePricesByGame = [];
        foreach ($averagePricesRaw as $row) {
            $gameId = (int) $row['gameId'];
            $currency = $row['currency'];
            if ($currency instanceof \BackedEnum) {
                $currency = $currency->value;
            }
            $averagePricesByGame[$gameId][$currency] = $row['averagePrice'] !== null ? (float) $row['averagePrice'] : null;
        }
        return $averagePricesByGame;
    }

    private function mapRowToGame(array $popularGamesRaw, array $averagePricesByGame): array
    {
        $popularGames = [];
        foreach ($popularGamesRaw as $row) {
            $game = $row[0];
            $gameId = $game->getId();
            
            $popularGames[] = [
                'id' => $gameId,
                'title' => $game->getTitle(),
                'console' => $game->getConsole(),
                'releaseYear' => $game->getReleaseYear(),
                'additionsCount' => (int) $row['additionsCount'],
                'averagePrices' => $averagePricesByGame[$gameId] ?? [],
            ];
        }
        return $popularGames;
    }
    
}

