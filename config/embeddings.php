<?php

/**
 * Конфигурация сервиса эмбеддингов.
 *
 * Параметры:
 * - base_url: базовый URL OpenAI‑совместимого API для эндпоинта /v1/embeddings.
 * - model: название модели эмбеддингов (например, intfloat/multilingual-e5-large).
 * - api_key: ключ авторизации (если шлюз требует Bearer‑токен).
 * - dimension: размерность векторов (должна совпадать со схемой PG vector(...)).
 */
return [
    // Базовый URL OpenAI‑совместимого API (шлюз эмбеддингов)
    'base_url' => env('EMBEDDINGS_BASE_URL', 'http://host.docker.internal:11434/v1'),

    // Имя модели эмбеддингов
    'model' => env('EMBEDDINGS_MODEL', 'intfloat/multilingual-e5-large'),

    // Необязательный Bearer‑токен для авторизации на шлюзе
    'api_key' => env('EMBEDDINGS_API_KEY'),

    // Размерность эмбеддингов
    'dimension' => (int) env('EMBEDDINGS_DIMENSION', 1024),
];
