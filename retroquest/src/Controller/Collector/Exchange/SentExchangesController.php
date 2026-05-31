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
class SentExchangesController extends AbstractController
{
    #[Route('/collector/exchange/sent', name: 'app_collector_exchange_sent', options: ['expose' => true])]
    public function listSent(
        ExchangeRepository $exchangeRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $exchanges = $exchangeRepository->findSentExchanges($currentUser);

        $exchangesData = [];
        foreach ($exchanges as $exchange) {
            $receiver = $exchange->getReceiver();

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

                if ($item->getCollector() === $receiver) {
                    $requestedItems[] = $itemData;
                } elseif ($item->getCollector() === $currentUser) {
                    $offeredItems[] = $itemData;
                }
            }

            $exchangesData[] = [
                'id' => $exchange->getId(),
                'status' => $exchange->getStatus()->value,
                'propositionDate' => $exchange->getPropositionDate()->format('d/m/Y H:i'),
                'receiver' => [
                    'id' => $receiver->getId(),
                    'email' => $receiver->getEmail(),
                ],
                'requestedItems' => $requestedItems,
                'offeredItems' => $offeredItems,
                'csrfTokenCancel' => $csrfTokenManager->getToken('cancel_exchange_' . $exchange->getId())->getValue(),
            ];
        }

        return $this->render('collector/exchange_sent.html.twig', [
            'exchangesData' => $exchangesData,
        ]);
    }
}
