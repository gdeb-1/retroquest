<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Form\CollectionItemType;
use App\Repository\CollectionItemRepository;
use App\Repository\GameRepository;
use App\Repository\ReviewRepository;
use App\Service\RawgService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

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

    #[Route('/collector/addCollectionItem', name: 'app_collector_add_collection_item', options: ['expose' => true])]
    public function addCollectionItem(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $collectionItem = new CollectionItem();
        $collectionItem->setCollector($this->getUser());
        $collectionItem->setAcquisitionDate(new \DateTime());

        $form = $this->createForm(CollectionItemType::class, $collectionItem);
        try{
            $form->handleRequest($request);
        }catch(\Exception $e){
            $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout du jeu à votre collection.');
            return $this->redirectToRoute('app_collector_add_collection_item');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($collectionItem);
            $entityManager->flush();

            $this->addFlash('success', 'Le jeu a été ajouté à votre collection.');

            return $this->redirectToRoute('app_collector_my_collection');
        }

        return $this->render('collector/add_collection_item.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/collector/deleteCollectionItem/{id}', name: 'app_collector_delete_collection_item', methods: ['POST'], options: ['expose' => true])]
    public function deleteCollectionItem(
        CollectionItem $collectionItem,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($collectionItem->getCollector() !== $user) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à supprimer ce jeu.");
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_collection_item', $token)) {
            $this->addFlash('error', 'Le jeton de sécurité est invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_collector_my_collection');
        }

        $entityManager->remove($collectionItem);
        $entityManager->flush();

        $this->addFlash('success', 'Le jeu a été retiré de votre collection.');

        return $this->redirectToRoute('app_collector_my_collection');
    }

    #[Route('/collector/game/{id}', name: 'app_collector_game_show', options: ['expose' => true])]
    public function show(
        Game $game,
        RawgService $rawgService,
        CacheInterface $cache,
        CollectionItemRepository $collectionItemRepository,
        ReviewRepository $reviewRepository
    ): Response {
        $gameId = $game->getId();
        $cacheKey = 'game_description_' . $gameId;

        $description = $cache->get($cacheKey, function (ItemInterface $item) use ($game, $rawgService) {
            $item->expiresAfter(3600 * 24 * 30);
            return $rawgService->fetchDescription($game->getTitle());
        });

        if ($description === null) {
            $cache->delete($cacheKey);
            $description = $rawgService->fetchDescription($game->getTitle());
            if ($description !== null) {
                $cache->get($cacheKey, function (ItemInterface $item) use ($description) {
                    $item->expiresAfter(3600 * 24 * 30);
                    return $description;
                });
            }
        }

        $collectionCount = $collectionItemRepository->count(['game' => $game]);
        $averagePricesRaw = $collectionItemRepository->findAveragePricesForGames([$game]);

        $averagePrices = [];
        foreach ($averagePricesRaw as $row) {
            $currency = $row['currency'];
            if ($currency instanceof \BackedEnum) {
                $currency = $currency->value;
            }
            $averagePrices[$currency] = $row['averagePrice'] !== null ? (float) $row['averagePrice'] : null;
        }

        $reviewsRaw = $reviewRepository->findBy(['game' => $game, 'isValid' => true], ['createdAt' => 'DESC']);
        $reviews = [];
        foreach ($reviewsRaw as $review) {
            $reviews[] = [
                'id' => $review->getId(),
                'comment' => $review->getComment(),
                'createdAt' => $review->getCreatedAt()->format('d/m/Y H:i'),
                'authorEmail' => $review->getAuthor()->getEmail(),
            ];
        }

        return $this->render('collector/game_show.html.twig', [
            'game' => $game,
            'description' => $description,
            'collectionCount' => $collectionCount,
            'averagePrices' => $averagePrices,
            'reviews' => $reviews,
        ]);
    }
}
