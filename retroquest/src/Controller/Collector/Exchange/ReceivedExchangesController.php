<?php

namespace App\Controller\Collector\Exchange;

use App\Entity\User;
use App\Repository\ExchangeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLECTOR')]
class ReceivedExchangesController extends AbstractController
{
    #[Route('/collector/exchange/received', name: 'app_collector_exchange_received', options: ['expose' => true])]
    public function listReceived(
        ExchangeRepository $exchangeRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $exchanges = $exchangeRepository->findReceivedExchanges($currentUser);

        $exchangesData = [];
        foreach ($exchanges as $exchange) {
            $proposer = $exchange->getProposer();

            $requestedItems = [];
            $offeredItems = [];

            foreach ($exchange->getItems() as $item) {
                $itemData = [
                    'id' => $item->getId(),
                    'state' => $item->getState()->value,
                    'acquisitionPrice' => $item->getAcquisitionPrice(),
                    'currency' => $item->getCurrency()->value,
                    'game' => [
                        'id' => $item->getGame()->getId(),
                        'title' => $item->getGame()->getTitle(),
                        'console' => $item->getGame()->getConsole(),
                    ],
                ];

                if ($item->getCollector() === $currentUser) {
                    $requestedItems[] = $itemData;
                } elseif ($item->getCollector() === $proposer) {
                    $offeredItems[] = $itemData;
                }
            }

            $exchangesData[] = [
                'id' => $exchange->getId(),
                'status' => $exchange->getStatus()->value,
                'propositionDate' => $exchange->getPropositionDate()->format('d/m/Y H:i'),
                'proposer' => [
                    'id' => $proposer->getId(),
                    'email' => $proposer->getEmail(),
                ],
                'requestedItems' => $requestedItems,
                'offeredItems' => $offeredItems,
                'csrfTokenReject' => $csrfTokenManager->getToken('reject_exchange_' . $exchange->getId())->getValue(),
            ];
        }

        return $this->render('collector/exchange_received.html.twig', [
            'exchangesData' => $exchangesData,
        ]);
    }
}
