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
class RejectExchangeController extends AbstractController
{
    #[Route('/collector/exchange/reject/{id}', name: 'app_collector_exchange_reject', methods: ['POST'], options: ['expose' => true])]
    public function reject(
        Exchange $exchange,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reject_exchange_' . $exchange->getId(), $token)) {
            $this->addFlash('error', 'Le jeton de sécurité est invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_collector_exchange_received');
        }

        if ($exchange->getReceiver() !== $currentUser) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à refuser cet échange.");
        }

        if ($exchange->getStatus() !== ExchangeStatuses::PENDING) {
            $this->addFlash('error', "Cet échange ne peut pas être refusé car il n'est plus en attente.");
            return $this->redirectToRoute('app_collector_exchange_received');
        }

        $exchange->setStatus(ExchangeStatuses::REJECTED);
        $entityManager->flush();

        $this->addFlash('success', "La proposition d'échange a bien été refusée.");
        return $this->redirectToRoute('app_collector_exchange_received');
    }
}
