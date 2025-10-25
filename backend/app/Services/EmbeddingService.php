<?php

namespace App\Services;

use App\Support\VectorMath;
use Exception;
use OpenAI\Client as OpenAIClient;
use OpenAI\Factory as OpenAIFactory;
use OpenAI\Responses\Embeddings\CreateResponseEmbedding;
use RuntimeException;

class EmbeddingService
{
    private OpenAIClient $client;
    private string $model;

    /**
     * Конструктор сервиса эмбеддингов.
     * Инициализирует HTTP‑клиент по OpenAI‑совместимому API.
     */
    public function __construct()
    {
        $this->model = config('embeddings.model');
        $this->client = $this->initOpenAIClient();
    }

    /**
     * Получить эмбеддинги для набора текстов.
     *
     * @param array $texts
     * @return array Вектора той же длины, что входные тексты
     */
    public function embedMany(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        try {
            $response = $this->client->embeddings()->create([
                'model' => $this->model,
                'input' => array_values($texts),
            ]);
        } catch (Exception $exception) {
            throw new RuntimeException(sprintf(__('exceptions.embedding.proposal_build'), $exception->getMessage()));
        }


        $vectors = array_map(fn(CreateResponseEmbedding $response) => $response->embedding, $response->embeddings);

        return VectorMath::l2normalizeEach($vectors);
    }

    /**
     * Получить эмбеддинг для одного текста (обертка над embedMany).
     *
     * @return array Вектор эмбеддинга
     */
    public function embedOne(string $text): array
    {
        $result = $this->embedMany([$text]);
        return $result[0] ?? [];
    }

    /**
     * @return OpenAIClient
     */
    public function initOpenAIClient(): OpenAIClient
    {
        $baseUrl = config('embeddings.base_url');
        $apiKey = config('embeddings.api_key');

        $factory = new OpenAIFactory()->withBaseUri($baseUrl);
        if (!empty($apiKey)) {
            $factory = $factory->withApiKey($apiKey);
        }
        return $factory->make();
    }
}


