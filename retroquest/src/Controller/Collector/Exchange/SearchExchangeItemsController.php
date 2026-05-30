<?php

namespace App\Controller\Collector\Exchange;

use App\Entity\User;
use App\Repository\CollectionItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLECTOR')]
class SearchExchangeItemsController extends AbstractController
{
    #[Route('/collector/exchange/search', name: 'app_collector_exchange_search', options: ['expose' => true])]
    public function search(CollectionItemRepository $collectionItemRepository): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $items = $collectionItemRepository->findAvailableForExchange($currentUser);

        $itemsData = [];
        foreach ($items as $item) {
            $game = $item->getGame();
            $collector = $item->getCollector();


            $itemsData[] = [
                'id' => $item->getId(),
                'state' => $item->getState()->value,
                'acquisitionPrice' => $item->getAcquisitionPrice(),
                'currency' => $item->getCurrency()->value,
                'collector' => [
                    'id' => $collector->getId(),
                    'email' => $collector->getEmail(),
                ],
                'game' => [
                    'id' => $game->getId(),
                    'title' => $game->getTitle(),
                    'console' => $game->getConsole(),
                    'releaseYear' => $game->getReleaseYear(),
                ],
            ];
        }

        return $this->render('collector/exchange_search.html.twig', [
            'itemsData' => $itemsData,
        ]);
    }
}
