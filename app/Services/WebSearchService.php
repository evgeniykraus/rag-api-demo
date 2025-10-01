<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\DTO\WebSearchOptionsData;

class WebSearchService
{
    /**
     * Выполняет поиск в интернете через Tavily API.
     * Возвращает полный ответ API (answer, images, results, meta и пр.).
     *
     * Поддерживаемые параметры options:
     * - topic: string (e.g. "general", "news")
     * - search_depth: string ("basic" | "advanced")
     * - include_images: bool
     * - include_answer: bool
     * - max_results: int
     *
     * @param string $query
     * @param WebSearchOptionsData $options
     * @return array
     */
    public static function search(string $query, WebSearchOptionsData $options = new WebSearchOptionsData()): array
    {
        $client = new Client([
            'base_uri' => config('tavily.base_url'),
            'timeout' => 10,
        ]);
        $apiKey = config('tavily.api_key');

        if (empty($apiKey)) {
            return [];
        }

        try {
            $payload = [
                'query' => $query,
            ];

            // Параметры из DTO
            $payload = array_merge($payload, $options->toArray());


            $response = $client->post('search', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'json' => $payload,
            ]);

            $data = json_decode((string)$response->getBody(), true);

            if (!is_array($data)) {
                return [];
            }

            // Возвращаем полный ответ Tavily, включая answer/images/results
            return $data;
        } catch (GuzzleException $e) {
            return [];
        }
    }
}


