<?php

namespace App\Controller\Collector\Exchange;

use App\Entity\Exchange;
use App\Enum\ExchangeStatuses;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLECTOR')]
class CancelExchangeController extends AbstractController
{
    #[Route('/collector/exchange/cancel/{id}', name: 'app_collector_exchange_cancel', methods: ['POST'], options: ['expose' => true])]
    public function cancel(
        Exchange $exchange,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('cancel_exchange_' . $exchange->getId(), $token)) {
            $this->addFlash('error', 'Le jeton de sécurité est invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_collector_exchange_sent');
        }

        if ($exchange->getProposer() !== $currentUser) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à annuler cet échange.");
        }

        if ($exchange->getStatus() !== ExchangeStatuses::PENDING) {
            $this->addFlash('error', "Cet échange ne peut pas être annulé car il n'est plus en attente.");
            return $this->redirectToRoute('app_collector_exchange_sent');
        }

        $exchange->setStatus(ExchangeStatuses::CANCELLED);
        $entityManager->flush();

        $this->addFlash('success', "La proposition d'échange a bien été annulée.");
        return $this->redirectToRoute('app_collector_exchange_sent');
    }
}
