<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class RawgService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $rawgApiKey
    ) {}

    /**
     * Fetches the plain text description of a game from RAWG API by title.
     *
     * @param string $title
     * @return string|null
     */
    public function fetchDescription(string $title): ?string
    {
        try {
            $searchResponse = $this->httpClient->request('GET', 'https://api.rawg.io/api/games', [
                'query' => [
                    'key' => $this->rawgApiKey,
                    'search' => $title,
                    'page_size' => 1,
                ]
            ]);

            if ($searchResponse->getStatusCode() !== 200) {
                $this->logger->error('RAWG search API returned status ' . $searchResponse->getStatusCode() . ' for ' . $title);
                return null;
            }

            $searchData = $searchResponse->toArray();
            if (empty($searchData['results'])) {
                $this->logger->info('No RAWG search results found for ' . $title);
                return null;
            }

            $gameId = $searchData['results'][0]['id'] ?? null;
            if (!$gameId) {
                return null;
            }

            $detailsResponse = $this->httpClient->request('GET', 'https://api.rawg.io/api/games/' . $gameId, [
                'query' => [
                    'key' => $this->rawgApiKey,
                ]
            ]);

            if ($detailsResponse->getStatusCode() !== 200) {
                $this->logger->error('RAWG details API returned status ' . $detailsResponse->getStatusCode() . ' for game ID ' . $gameId);
                return null;
            }

            $detailsData = $detailsResponse->toArray();
            
            return $detailsData['description_raw'] ?? $detailsData['description'] ?? null;

        } catch (\Exception $e) {
            $this->logger->error('Exception when fetching description from RAWG: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return null;
        }
    }
}
