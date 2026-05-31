<?php

namespace App\Service;

use App\Entity\Exchange;
use App\Entity\User;
use App\Enum\ExchangeStatuses;

class ExchangeService
{
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
}
