<?php

namespace App\Service;

use App\Entity\Exchange;
use App\Entity\User;
use App\Enum\ExchangeStatuses;
use App\Exception\InvalidStateExchangeException;
use App\Exception\NotEligibleExchangeException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

class ExchangeService
{
    public function __construct(
        private ExchangeEligibilityService $exchangeEligibilityService,
        #[Target('exchange_status')]
        private WorkflowInterface $exchangeWorkflow
    ) {}

    /**
     * Valide un échange en attente.
     *
     * Cela passe le statut à ACCEPTED, échange la propriété de tous les objets de l'échange
     * entre le proposant et le destinataire, et annule automatiquement les autres échanges
     * en attente qui impliquent ces mêmes objets.
     *
     * @throws InvalidStateExchangeException Si l'échange n'est pas en attente.
     * @throws NotEligibleExchangeException Si l'échange n'est pas éligible.
     */
    public function validateExchange(Exchange $exchange): void
    {
        if ($exchange->getStatus() !== ExchangeStatuses::PENDING) {
            throw new InvalidStateExchangeException("Seuls les échanges en attente peuvent être validés.");
        }

        if (!$this->exchangeEligibilityService->isDirectExchangeEligible($exchange)) {
            throw new NotEligibleExchangeException("Cet échange n'est pas éligible et ne peut pas être validé.");
        }

        $this->exchangeWorkflow->apply($exchange, 'validate');
    }
}

