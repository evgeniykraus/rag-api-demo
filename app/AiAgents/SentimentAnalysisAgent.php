<?php

namespace App\AiAgents;

use LarAgent\Agent;

class SentimentAnalysisAgent extends Agent
{
//    protected $model = 'multilingual-sentiment-analysis';
    protected $model = 'meta-llama-3-8b-instruct';
    protected $history = 'in_memory';
    protected $provider = 'default';
    protected $temperature = 0.0;

    protected $responseSchema = [
        'name' => 'sentiment_classification',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'sentiment' => [
                    'type' => 'string',
                    'enum' => ['positive', 'negative', 'neutral', 'meaningless'],
                    'description' => 'Тональность текста',
                ],
                'confidence' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                    'description' => 'Уровень уверенности в классификации (0.0-1.0)',
                ],
            ],
            'required' => ['sentiment', 'confidence'],
            'additionalProperties' => false,
        ],
        'strict' => true,
    ];

    public function instructions(): string
    {
        return __('prompts.text_classification_prompt');
    }
}
