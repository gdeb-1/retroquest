<?php

namespace App\Controller\Collector\Collection;

use App\Entity\User;
use App\Repository\CollectionItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLECTOR')]
class ShowUserCollectionController extends AbstractController
{
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
