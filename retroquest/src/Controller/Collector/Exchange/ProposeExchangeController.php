<?php

namespace App\Controller\Collector\Exchange;

use App\Entity\CollectionItem;
use App\Entity\Exchange;
use App\Enum\ExchangeStatuses;
use App\Form\ExchangeType;
use App\Repository\CollectionItemRepository;
use App\Repository\ExchangeRepository;
use App\Service\ExchangeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLECTOR')]
class ProposeExchangeController extends AbstractController
{
    #[Route('/collector/exchange/propose/{id}', name: 'app_collector_exchange_propose', methods: ['GET', 'POST'], options: ['expose' => true])]
    public function propose(
        CollectionItem $receiverItem,
        Request $request,
        EntityManagerInterface $entityManager,
        ExchangeService $exchangeService,
        CollectionItemRepository $collectionItemRepository,
        ExchangeRepository $exchangeRepository
    ): Response {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        if ($receiverItem->getCollector() === $currentUser) {
            $this->addFlash('error', 'Vous ne pouvez pas proposer un échange pour votre propre jeu.');
            return $this->redirectToRoute('app_collector_exchange_search');
        }

        $availableItems = $collectionItemRepository->findAvailableForExchangeByCollector($currentUser);

        $pendingExchanges = $exchangeRepository->findPendingExchangesForCollectionItemBetweenUsers(
            $receiverItem,
            $currentUser,
            $receiverItem->getCollector()
        );

        $exchange = new Exchange();
        $exchange->setProposer($currentUser);
        $exchange->setReceiver($receiverItem->getCollector());
        $exchange->setStatus(ExchangeStatuses::PENDING);
        $exchange->setPropositionDate(new \DateTime());

        $form = $this->createForm(ExchangeType::class, $exchange, [
            'proposer' => $currentUser,
            'available_items' => $availableItems,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $exchange->addItem($receiverItem);

            $offeredItems = $form->get('offeredItems')->getData();
            foreach ($offeredItems as $item) {
                $exchange->addItem($item);
            }

            if ($exchangeService->isDirectExchangeEligible($exchange)) {
                $entityManager->persist($exchange);
                $entityManager->flush();

                $this->addFlash('success', 'Votre proposition d\'échange a bien été envoyée.');
                return $this->redirectToRoute('app_collector_exchange_search');
            }

            $this->addFlash('error', 'L\'échange proposé n\'est pas éligible ou l\'un des objets est déjà impliqué dans un échange en attente.');
        }

        return $this->render('collector/exchange_propose.html.twig', [
            'receiverItem' => $receiverItem,
            'form' => $form->createView(),
            'hasAvailableItems' => count($availableItems) > 0,
            'pendingExchanges' => $pendingExchanges,
        ]);
    }
}
