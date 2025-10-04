<?php

namespace App\AiAgents\MetaDataExtractor;

use LarAgent\Agent;

class ResolutionAgent extends Agent
{
    protected $history = 'in_memory';
    protected $temperature = 0.0;

    protected $responseSchema = [
        'name' => 'resolution_assessment',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'resolution_likelihood' => [
                    'type' => 'number', 'minimum' => 0, 'maximum' => 1,
                    'description' => 'Вероятность, что ответ приведёт к закрытию обращения (0..1)'
                ],
                'followup_needed' => [
                    'type' => 'boolean',
                    'description' => 'Нужно ли дополнительное уточнение/контакт'
                ],
                'next_steps' => [
                    'type' => 'array', 'items' => [ 'type' => 'string' ],
                    'description' => 'Краткие следующие шаги, если они требуются'
                ]
            ],
            'required' => ['resolution_likelihood','followup_needed','next_steps'],
            'additionalProperties' => false
        ],
        'strict' => true
    ];

    public function instructions(): string
    {
        return (
            'Ты — агент оценки вероятности закрытия обращения данным ответом. '
            .'Верни JSON: resolution_likelihood (0..1), followup_needed (нужны ли уточнения), next_steps (краткие шаги при необходимости на русском языке).'
        );
    }
}


