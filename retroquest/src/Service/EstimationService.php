<?php

namespace App\Service;

use App\Entity\User;
use App\Enum\Currency;
use App\Repository\CollectionItemRepository;

class EstimationService
{
    public function __construct(
        private CollectionItemRepository $collectionItemRepository
    ) {}

    /**
     * Calcule la valeur estimée de la collection d'un utilisateur par devise.
     * La valeur d'un item est basée sur la moyenne des prix d'acquisition
     * pour le même jeu, dans le même état, dans la même devise.
     *
     * @param User $collector
     * @return array<string, int> Exemple : ['EUR' => 12500, 'USD' => 0, 'GBP' => 0]
     */
    public function calculateCollectionValue(User $collector): array
    {
        $collectionItems = $collector->getCollectionItems();
        
        $totals = [];
        // Initialiser toutes les devises supportées à 0
        foreach (Currency::cases() as $currency) {
            $totals[$currency->value] = 0;
        }

        if ($collectionItems->isEmpty()) {
            return $totals;
        }

        // Récupérer la liste des jeux uniques dans la collection du membre
        $games = [];
        foreach ($collectionItems as $item) {
            $game = $item->getGame();
            if ($game) {
                $games[$game->getId()] = $game;
            }
        }

        // Récupérer tous les items en base pour ces jeux (les éléments pour le calcul)
        $allMatchingItems = $this->collectionItemRepository->findForGames(array_values($games));
        
        // Grouper les prix d'acquisition par [gameId][state][currency]
        $groupedPrices = [];
        foreach ($allMatchingItems as $item) {
            $game = $item->getGame();
            $state = $item->getState()?->value;
            $currency = $item->getCurrency()?->value;
            
            if ($game && $state && $currency) {
                $groupedPrices[$game->getId()][$state][$currency][] = $item->getAcquisitionPrice();
            }
        }

        // Calculer les moyennes par [gameId][state][currency]
        $averagePrices = [];
        foreach ($groupedPrices as $gameId => $states) {
            foreach ($states as $state => $currencies) {
                foreach ($currencies as $currency => $prices) {
                    $averagePrices[$gameId][$state][$currency] = array_sum($prices) / count($prices);
                }
            }
        }

        // Estimer la valeur totale par devise
        foreach ($collectionItems as $item) {
            $game = $item->getGame();
            $state = $item->getState()?->value;
            $currency = $item->getCurrency()?->value;

            if ($game && $state && $currency && isset($averagePrices[$game->getId()][$state][$currency])) {
                $avgPrice = $averagePrices[$game->getId()][$state][$currency];
                $totals[$currency] += (int)round($avgPrice);
            } else {
                // Valeur de secours : le prix d'acquisition de l'item lui-même
                $totals[$currency] += $item->getAcquisitionPrice();
            }
        }

        return $totals;
    }
}
