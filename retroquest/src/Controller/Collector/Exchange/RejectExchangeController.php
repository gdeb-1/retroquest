<?php

namespace App\Controller\Collector\Exchange;

use App\Message\Command\Exchange\RejectExchangeCommand;
use App\Exception\ExchangeNotFoundException;
use App\Exception\UserNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLECTOR')]
class RejectExchangeController extends AbstractController
{
    #[Route('/collector/exchange/reject/{id}', name: 'app_collector_exchange_reject', methods: ['POST'], options: ['expose' => true])]
    public function reject(
        int $id,
        Request $request,
        MessageBusInterface $messageBus
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reject_exchange_' . $id, $token)) {
            $this->addFlash('error', 'Le jeton de sécurité est invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_collector_exchange_received');
        }

        try {
            $messageBus->dispatch(new RejectExchangeCommand($id, $this->getUser()->getId()));

            $this->addFlash('success', "La proposition d'échange a bien été refusée.");
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof AccessDeniedException) {
                throw $previous;
            }
            if ($previous instanceof ExchangeNotFoundException || $previous instanceof UserNotFoundException) {
                throw new NotFoundHttpException($previous->getMessage(), $previous);
            }
            if ($previous instanceof \LogicException) {
                $this->addFlash('error', $previous->getMessage());
            } else {
                throw $e;
            }
        }

        return $this->redirectToRoute('app_collector_exchange_received');
    }
}
