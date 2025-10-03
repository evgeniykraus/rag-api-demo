<?php

namespace App\AiAgents\MetaDataExtractor;

use LarAgent\Agent;

class ToneClarityAgent extends Agent
{
    protected $model = 'meta-llama-3-8b-instruct';
    protected $history = 'in_memory';
    protected $temperature = 0.0;

    protected $responseSchema = [
        'name' => 'tone_clarity',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'tone_politeness_score' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                    'description' => 'Оценка вежливости и доброжелательности тона ответа (0..1)'
                ],
                'clarity_score' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                    'description' => 'Оценка ясности и простоты формулировок (0..1)'
                ],
                'jargon_flag' => [
                    'type' => 'boolean',
                    'description' => 'Флаг наличия канцелярита/жаргона в ответе'
                ]
            ],
            'required' => ['tone_politeness_score','clarity_score','jargon_flag'],
            'additionalProperties' => false
        ],
        'strict' => true
    ];

    public function instructions(): string
    {
        return (
            'Ты — агент оценки тона и понятности ответа менеджера. '
            .'Проанализируй только текст ответа. '
            .'Верни JSON: tone_politeness_score, clarity_score (0..1), jargon_flag (true если канцелярит/жаргон).'
        );
    }
}


