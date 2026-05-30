<?php

namespace App\Controller\Collector\Game;

use App\Entity\Game;
use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\CollectionItemRepository;
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
class ShowGameController extends AbstractController
{
    #[Route('/collector/game/{id}', name: 'app_collector_game_show', methods: ['GET', 'POST'], options: ['expose' => true])]
    public function show(
        Game $game,
        RawgService $rawgService,
        CacheInterface $cache,
        CollectionItemRepository $collectionItemRepository,
        ReviewRepository $reviewRepository,
        Request $request,
        EntityManagerInterface $entityManager
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

        $user = $this->getUser();
        $userReview = $reviewRepository->findOneBy(['game' => $game, 'author' => $user]);
        $hasReviewed = $userReview !== null;

        $newReview = new Review();
        $form = $this->createForm(ReviewType::class, $newReview);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                if ($hasReviewed) {
                    $this->addFlash('error', 'Vous avez déjà laissé un avis sur ce jeu.');
                    return $this->redirectToRoute('app_collector_game_show', ['id' => $gameId]);
                }

                $newReview->setGame($game);
                $newReview->setAuthor($user);
                $newReview->setIsValid(false);
                $newReview->setCreatedAt(new \DateTimeImmutable());

                $entityManager->persist($newReview);
                $entityManager->flush();

                $this->addFlash('success', 'Votre avis a été soumis avec succès et est en attente de modération.');
                return $this->redirectToRoute('app_collector_game_show', ['id' => $gameId]);
            }
        }

        return $this->render('collector/game_show.html.twig', [
            'game' => $game,
            'description' => $description,
            'collectionCount' => $collectionCount,
            'averagePrices' => $averagePrices,
            'reviews' => $reviews,
            'userReview' =>$userReview,
            'hasReviewed' => $hasReviewed,
            'form' => $form->createView(),
        ]);
    }
}
