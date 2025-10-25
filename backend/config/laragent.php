<?php

// config for Maestroerror/LarAgent
return [

    /**
     * Default driver to use, binded in service provider
     * with \LarAgent\Core\Contracts\LlmDriver interface
     */
    'default_driver' => \LarAgent\Drivers\OpenAi\OpenAiCompatible::class,

    /**
     * Default chat history to use, binded in service provider
     * with \LarAgent\Core\Contracts\ChatHistory interface
     */
    'default_chat_history' => \LarAgent\History\InMemoryChatHistory::class,

    /**
     * Always keep provider named 'default'
     * You can add more providers in array
     * by copying the 'default' provider
     * and changing the name and values
     */
    'providers' => [
        'default' => [
            'label' => 'openai',
            'model' => env('OPENAI_MODEL', 'meta-llama-3-8b-instruct'),
            'api_url' => env('OPENAI_BASE_URL'),
            'api_key' => env('OPENAI_API_KEY'),
            'default_context_window' => 50000,
            'default_max_completion_tokens' => 10000,
            'default_temperature' => 1,
        ],

        'ollama' => [
            'name' => 'ollama',
            'model' => 'gemma3:4b',
            'driver' => \LarAgent\Drivers\OpenAi\OllamaDriver::class,
            'api_key' => "ollama",
            'api_url' => env('OLLAMA_BASE_URL'),
            'default_context_window' => 50000,
            'default_max_completion_tokens' => 100,
            'default_temperature' => 1,
        ],
    ],
];
