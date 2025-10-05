<?php

namespace App\AiAgents;

use LarAgent\Agent;

class ImageToTextAgent extends Agent
{
    protected $apiUrl = 'http://host.docker.internal:1234/v1';
    protected $model = 'qwen2-vl-2b-instruct';
    protected $history = 'in_memory';
    protected $provider = 'default';
    protected $temperature = 0.3;

    protected $responseSchema = [
        'name' => 'photo_description',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'description' => [
                    'type' => 'string',
                    'description' => 'Описание содержимого на фото',
                ],
            ],
            'required' => ['description'],
            'additionalProperties' => false,
        ],
        'strict' => true,
    ];

    public function instructions(): string
    {
        return "Ты описываешь фотографии на русском языке. Чаще всего, на фотографиях может присутствовать какая-либо проблема.\n
                Описание должно быть утвердительным\n\n
                Например:\n
                    - Неубранная придомовая территория\n
                    - Двор засыпанный снегом\n
                    - Разбитый тротуар";
    }
}
