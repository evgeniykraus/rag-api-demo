<?php

return [
    'base_url' => env('GIGACHAT_BASE_URL', 'https://gigachat.devices.sberbank.ru'),
    'ngw_base_url' => env('GIGACHAT_NGW_BASE_URL', 'https://ngw.devices.sberbank.ru:9443'),
    'token' => env('GIGACHAT_TOKEN'),
    'scope' => env('GIGACHAT_SCOPE', 'GIGACHAT_API_PERS'),
    'model' => env('GIGACHAT_MODEL', 'GigaChat:latest'),
    'max_tokens' => env('GIGACHAT_MAX_TOKENS', 400),

    // Параметры для эмбеддингов
    'embeddings_model' => env('GIGACHAT_EMBEDDINGS_MODEL', 'Embeddings'),
    'embeddings_dimension' => (int) env('GIGACHAT_EMBEDDINGS_DIMENSION', 1024),
];


