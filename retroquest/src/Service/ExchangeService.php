<?php

namespace App\Service;

use App\Entity\Exchange;
use App\Entity\User;
use App\Enum\ExchangeStatuses;
use App\Exception\InvalidStateExchangeException;
use App\Exception\NotEligibleExchangeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

class ExchangeService
{
    public function __construct(
        #[Target('exchange_status')]
        #[Autowire(lazy: true)]
        private WorkflowInterface $exchangeWorkflow
    ) {}
    /**
     * Vérifie si un échange direct entre deux membres est éligible en fonction des objets de leur collection.
     *
     * Règles d'éligibilité :
     * 1. Acteurs distincts : Le proposant (proposer) et le destinataire (receiver) doivent être définis et différents.
     * 2. Échange bilatéral : L'échange doit contenir au moins un objet proposé par le proposant ET au moins un objet proposé par le destinataire.
     * 3. Propriété exclusive : Chaque objet impliqué dans l'échange doit appartenir soit au proposant, soit au destinataire. Aucun objet tiers n'est autorisé.
     * 4. Anti-doublon : La combinaison exacte d'objets ne peut pas être associée à un autre échange déjà en attente (PENDING) entre ces deux participants.
     */
    public function isDirectExchangeEligible(Exchange $exchange): bool
    {
        $proposer = $exchange->getProposer();
        $receiver = $exchange->getReceiver();

        if ($proposer === null || $receiver === null) {
            return false;
        }

        if ($proposer === $receiver) {
            return false;
        }

        $items = $exchange->getItems();
        if ($items->isEmpty()) {
            return false;
        }

        $proposerOfferedCount = 0;
        $receiverOfferedCount = 0;

        foreach ($items as $item) {
            $collector = $item->getCollector();

            if ($collector === $proposer) {
                $proposerOfferedCount++;
            } elseif ($collector === $receiver) {
                $receiverOfferedCount++;
            } else {
                return false;
            }
            // Règle d'anti-doublon pour les échanges en attente (PENDING).
            // Comme chaque CollectionItem possède un propriétaire unique (collector) et que tous les
            // objets d'un échange doivent appartenir aux deux participants, comparer la combinaison exacte
            // d'objets garantit implicitement que les participants impliqués sont également les mêmes.
            foreach ($item->getExchanges() as $otherExchange) {
                if ($otherExchange !== $exchange && $otherExchange->getStatus() === ExchangeStatuses::PENDING) {
                    $otherItems = $otherExchange->getItems();
                    if ($otherItems->count() === $items->count()) {
                        $sameItems = true;
                        foreach ($items as $currentItem) {
                            if (!$otherItems->contains($currentItem)) {
                                $sameItems = false;
                                break;
                            }
                        }
                        if ($sameItems) {
                            return false;
                        }
                    }
                }
            }
        }

        if ($proposerOfferedCount === 0 || $receiverOfferedCount === 0) {
            return false;
        }

        return true;
    }

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

        if (!$this->isDirectExchangeEligible($exchange)) {
            throw new NotEligibleExchangeException("Cet échange n'est pas éligible et ne peut pas être validé.");
        }

        $this->exchangeWorkflow->apply($exchange, 'validate');
    }

    /**
     * Effectue le transfert de propriété des objets impliqués dans l'échange.
     */
    public function processExchangeTransfer(Exchange $exchange): void
    {
        $proposer = $exchange->getProposer();
        $receiver = $exchange->getReceiver();

        foreach ($exchange->getItems() as $item) {
            $currentCollector = $item->getCollector();
            if ($currentCollector === $proposer) {
                $proposer->removeCollectionItem($item);
                $receiver->addCollectionItem($item);
            } elseif ($currentCollector === $receiver) {
                $receiver->removeCollectionItem($item);
                $proposer->addCollectionItem($item);
            }
        }
    }
}

