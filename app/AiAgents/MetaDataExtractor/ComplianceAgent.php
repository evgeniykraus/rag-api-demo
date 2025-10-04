<?php

namespace App\AiAgents\MetaDataExtractor;

use LarAgent\Agent;

class ComplianceAgent extends Agent
{
    protected $history = 'in_memory';
    protected $temperature = 0.0;

    protected $responseSchema = [
        'name' => 'policy_compliance',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'policy_compliance_score' => [
                    'type' => 'number', 'minimum' => 0, 'maximum' => 1,
                    'description' => 'Оценка соответствия базовым правилам и регламентам (0..1)'
                ],
                'risk_flags' => [
                    'type' => 'array',
                    'items' => [ 'type' => 'string', 'enum' => ['personal_data','legal_risk','incorrect_commitment'] ],
                    'description' => 'Флаги потенциальных рисков: персональные данные, юридические риски, некорректные обещания'
                ]
            ],
            'required' => ['policy_compliance_score','risk_flags'],
            'additionalProperties' => false
        ],
        'strict' => true
    ];

    public function instructions(): string
    {
        return (
            'Ты — агент проверки соответствия базовым правилам. '
            .'Проанализируй ответ менеджера на наличие ПДн, юридических рисков, некорректных обещаний. '
            .'Верни JSON: policy_compliance_score (0..1) и risk_flags из набора '
            .'[personal_data, legal_risk, incorrect_commitment].'
        );
    }
}


