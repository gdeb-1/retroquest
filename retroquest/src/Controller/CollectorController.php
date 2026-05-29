<?php

namespace App\Controller;

use App\Repository\CollectionItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLECTOR')]
class CollectorController extends AbstractController
{
    #[Route('/collector/GuildCatalog', name: 'app_collector_guild_catalog', options: ['expose' => true])]
    public function guildCatalog(CollectionItemRepository $collectionItemRepository): Response
    {
        $collectionItems = $collectionItemRepository->findAllWithGameAndCollector();

        $catalogData = [];
        foreach ($collectionItems as $item) {
            $catalogData[] = [
                'id' => $item->getId(),
                'title' => $item->getGame()->getTitle(),
                'console' => $item->getGame()->getConsole(),
                'releaseYear' => $item->getGame()->getReleaseYear(),
                'state' => $item->getState()->value,
                'acquisitionPrice' => $item->getAcquisitionPrice(),
                'currency' => $item->getCurrency()->value,
                'acquisitionDate' => $item->getAcquisitionDate() ? $item->getAcquisitionDate()->format('Y-m-d') : null,
                'collector' => $item->getCollector()->getEmail(),
            ];
        }

        return $this->render('collector/guild_catalog.html.twig', [
            'catalogData' => $catalogData,
        ]);
    }
}
