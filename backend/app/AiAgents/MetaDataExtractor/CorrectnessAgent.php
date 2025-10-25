<?php

namespace App\AiAgents\MetaDataExtractor;

use LarAgent\Agent;

class CorrectnessAgent extends Agent
{
    protected $history = 'in_memory';
    protected $temperature = 0.0;

    protected $responseSchema = [
        'name' => 'answer_correctness',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'correctness_score' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                    'description' => 'Насколько ответ соответствует сути обращения (0..1)'
                ],
                'completeness_score' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                    'description' => 'Полнота покрытия ключевых пунктов (0..1)'
                ],
                'missing_points' => [
                    'type' => 'array',
                    'items' => [ 'type' => 'string' ],
                    'description' => 'Список тем/пунктов, не отражённых в ответе'
                ],
                'actionable_score' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                    'description' => 'Есть ли понятные действия/сроки/контакты (0..1)'
                ]
            ],
            'required' => ['correctness_score','completeness_score','actionable_score','missing_points'],
            'additionalProperties' => false,
        ],
        'strict' => true,
    ];

    public function instructions(): string
    {
        return (
            'Ты — агент проверки корректности ответа. У тебя есть текст обращения (вопрос) и ответ менеджера. '
            .'Оцени соответствие и полноту ответа относительно запроса. '
            .'Верни JSON строго по схеме: correctness_score, completeness_score, actionable_score, missing_points. '
            .'Баллы в диапазоне 0..1. missing_points — краткие фразы без пояснений.'
        );
    }
}


