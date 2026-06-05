<?php

namespace App\Controller\Collector\Catalog;

use App\Repository\GameRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLECTOR')]
class ListGuildCatalogController extends AbstractController
{
    #[Route('/collector/GuildCatalog', name: 'app_collector_guild_catalog', options: ['expose' => true])]
    public function guildCatalog(): Response
    {
        return $this->render('collector/guild_catalog.html.twig');
    }

    #[Route('/collector/GuildCatalog/data', name: 'app_collector_guild_catalog_data', options: ['expose' => true], methods: ['GET'])]
    public function guildCatalogData(Request $request, GameRepository $gameRepository): JsonResponse
    {
        $draw = $request->query->getInt('draw', 1);
        $start = $request->query->getInt('start', 0);
        $length = $request->query->getInt('length', 10);

        // Clamp the length parameter to prevent Denial of Service (DoS) attacks
        if ($length < 1 || $length > 100) {
            $length = 10;
        }

        $searchParams = $request->query->all('search');
        $searchValue = isset($searchParams['value']) ? (string)$searchParams['value'] : null;

        $orderParams = $request->query->all('order');
        $columnsParams = $request->query->all('columns');

        $sortColumn = null;
        $sortDir = 'asc';

        if (!empty($orderParams) && isset($orderParams[0]['column'])) {
            $columnIndex = (int)$orderParams[0]['column'];
            $sortDir = isset($orderParams[0]['dir']) && strtolower($orderParams[0]['dir']) === 'desc' ? 'desc' : 'asc';

            // Safely map sort column index to allowed DB column properties
            if (isset($columnsParams[$columnIndex]['data'])) {
                $columnData = (string)$columnsParams[$columnIndex]['data'];
                if (in_array($columnData, ['title', 'console', 'releaseYear'], true)) {
                    $sortColumn = $columnData;
                }
            }
        }

        $totalRecords = $gameRepository->countVisibleGames();
        $filteredRecords = $gameRepository->countFilteredCatalog($searchValue);
        $games = $gameRepository->getPaginatedCatalog($start, $length, $searchValue, $sortColumn, $sortDir);

        $data = [];
        foreach ($games as $game) {
            $data[] = [
                'id' => $game->getId(),
                'title' => $game->getTitle(),
                'console' => $game->getConsole(),
                'releaseYear' => $game->getReleaseYear(),
            ];
        }

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }
}
