<?php

namespace App\Service;

use App\Entity\Exchange;
use App\Entity\User;
use App\Enum\ExchangeStatuses;

class ExchangeService
{
    /**
     * Checks if a direct exchange between two members is eligible based on their collection items.
     *
     * Rules:
     * 1. Proposer and Receiver must be different.
     * 2. The exchange must contain at least one item from the Proposer and at least one item from the Receiver.
     * 3. Every item in the exchange must belong to either the Proposer or the Receiver.
     * 4. None of the items in the exchange can be associated with another active exchange (PENDING or ACCEPTED).
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

            foreach ($item->getExchanges() as $otherExchange) {
                if ($otherExchange !== $exchange) {
                    $otherStatus = $otherExchange->getStatus();
                    if ($otherStatus === ExchangeStatuses::PENDING || $otherStatus === ExchangeStatuses::ACCEPTED) {
                        return false;
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
