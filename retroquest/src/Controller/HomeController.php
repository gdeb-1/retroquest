<?php

namespace App\Controller;

use App\Repository\GameRepository;
use App\Repository\CollectionItemRepository;
use App\Service\RawgService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class HomeController extends AbstractController
{
    public function __construct(
        private RawgService $rawgService,
        private CacheInterface $cache
    ) {}
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
            
            $description = $this->cache->get('game_description_' . $gameId, function (ItemInterface $item) use ($game) {
                $item->expiresAfter(3600 * 24 * 30);
                return $this->rawgService->fetchDescription($game->getTitle());
            });
            
            $popularGames[] = [
                'id' => $gameId,
                'title' => $game->getTitle(),
                'console' => $game->getConsole(),
                'releaseYear' => $game->getReleaseYear(),
                'additionsCount' => (int) $row['additionsCount'],
                'averagePrices' => $averagePricesByGame[$gameId] ?? [],
                'description' => $description,
            ];
        }
        return $popularGames;
    }
    
}

