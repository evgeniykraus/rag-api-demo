<?php

namespace App\AiAgents;

use LarAgent\Agent;

class SentimentAnalysisAgent extends Agent
{
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
        return <<<'PROMPT'
        Ты — эксперт по анализу тональности русского текста.

        Задача: классифицировать данный текст как один из вариантов:
        "positive" — положительный (явно выражается благодарность, восхищение),
        "negative" — отрицательный (явно выражается недовольство),
        "neutral" — нейтральный (констатация фактов, нейтральный текст),
        "meaningless" — бессмысленный (несвязный текст, шум или случайные символы.)
        PROMPT;
    }
}
