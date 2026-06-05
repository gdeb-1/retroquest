<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Exchange;
use App\Service\ExchangeEligibilityService;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\WorkflowInterface;

class ExchangeWorkflowListener
{
    public function __construct(
        private ExchangeEligibilityService $exchangeEligibilityService,
        #[Target('exchange_status')]
        private WorkflowInterface $exchangeWorkflow
    ) {}

    #[AsGuardListener(workflow: 'exchange_status', transition: 'validate')]
    public function onValidateGuard(GuardEvent $event): void
    {
        $exchange = $event->getSubject();
        if (!$exchange instanceof Exchange) {
            return;
        }

        if (!$this->exchangeEligibilityService->isDirectExchangeEligible($exchange)) {
            $event->setBlocked(true, "Cet échange n'est pas éligible et ne peut pas être validé.");
        }
    }

    #[AsTransitionListener(workflow: 'exchange_status', transition: 'validate')]
    public function onValidateTransition(TransitionEvent $event): void
    {
        $exchange = $event->getSubject();
        if (!$exchange instanceof Exchange) {
            return;
        }

        // 1. Swap ownership of items
        $this->exchangeEligibilityService->processExchangeTransfer($exchange);

        // 2. Cancel conflicting pending exchanges
        foreach ($exchange->getItems() as $item) {
            foreach ($item->getExchanges() as $otherExchange) {
                if ($otherExchange !== $exchange && $this->exchangeWorkflow->can($otherExchange, 'cancel')) {
                    $this->exchangeWorkflow->apply($otherExchange, 'cancel');
                }
            }
        }
    }
}
