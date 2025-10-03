<?php

namespace App\AiAgents\MetaDataExtractor;

use App\Enums\IntentTagsEnum;
use LarAgent\Agent;

class EntitiesTagsAgent extends Agent
{
    protected $model = 'meta-llama-3-8b-instruct';
    protected $history = 'in_memory';
    protected $temperature = 0.0;

    protected $responseSchema = [
        'name' => 'entities_tags',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'intent_tags' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => [
                            'report_problem',
                            'request_resolution',
                            'complaint',
                            'suggestion',
                            'information_request',
                            'urgent_request',
                            'maintenance_request',
                            'safety_issue',
                            'environmental_issue',
                            'infrastructure_issue',
                            'housing_issue',
                            'transport_issue',
                            'utility_issue',
                            'cleanliness_issue',
                            'noise_complaint',
                            'parking_issue',
                            'lighting_issue',
                            'road_issue',
                            'sidewalk_issue',
                            'green_space_issue',
                            'public_service_issue',
                            'administrative_request',
                            'legal_question',
                            'thank_you',
                            'follow_up'
                        ]
                    ],
                    'description' => 'Короткие тематические теги (до 6) из предопределенного списка'
                ],
                'entities' => [
                    'type' => 'object',
                    'properties' => [
                        'locations' => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
                        'objects' => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ]
                    ],
                    'required' => ['locations','objects'],
                    'additionalProperties' => false
                ]
            ],
            'required' => ['intent_tags','entities'],
            'additionalProperties' => false
        ],
        'strict' => true
    ];

    public function instructions(): string
    {
        $availableTags = implode(', ', IntentTagsEnum::getValues());

        return (
            'Ты — агент извлечения тегов и сущностей из пары "обращение + ответ". '
            .'Верни до 6 коротких intent_tags из предопределенного списка: ' . $availableTags . '. '
            .'Также извлеки entities.locations (адрес/улица/ориентир) и entities.objects (дороги, освещение, ТКО и др.). '
            .'Строго следуй схеме JSON и используй только теги из списка.'
        );
    }
}


