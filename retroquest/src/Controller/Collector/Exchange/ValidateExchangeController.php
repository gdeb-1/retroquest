<?php

namespace App\Controller\Collector\Exchange;

use App\Entity\Exchange;
use App\Exception\InvalidStateExchangeException;
use App\Exception\NotEligibleExchangeException;
use App\Service\ExchangeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLECTOR')]
class ValidateExchangeController extends AbstractController
{
    #[Route('/collector/exchange/validate/{id}', name: 'app_collector_exchange_validate', methods: ['POST'], options: ['expose' => true])]
    public function validate(
        Exchange $exchange,
        Request $request,
        ExchangeService $exchangeService,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('validate_exchange_' . $exchange->getId(), $token)) {
            $this->addFlash('error', 'Le jeton de sécurité est invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_collector_exchange_received');
        }

        if ($exchange->getReceiver() !== $currentUser) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à valider cet échange.");
        }

        try {
            $exchangeService->validateExchange($exchange);
            $entityManager->flush();

            $this->addFlash('success', "La proposition d'échange a bien été validée et la propriété des objets a été transférée.");
        } catch (InvalidStateExchangeException|NotEligibleExchangeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_collector_exchange_received');
    }
}
