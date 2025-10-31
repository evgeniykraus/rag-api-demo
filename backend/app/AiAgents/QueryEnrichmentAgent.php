<?php

namespace App\AiAgents;

use App\Repositories\ProposalRepository;
use App\Services\EmbeddingService;
use LarAgent\Agent;
use LarAgent\Attributes\Tool;
use Pgvector\Vector;

class QueryEnrichmentAgent extends Agent
{
    protected $history = 'in_memory';
    protected $provider = 'default';
    protected $temperature = 0.3;

    protected $tools = [];
    protected $responseSchema = [
        'name' => 'expanded_query',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'expanded_query' => [
                    'type' => 'string',
                    'description' => 'Обогащённый и уточнённый поисковый запрос для RAG-системы'
                ],
            ],
            'required' => ['expanded_query'],
            'additionalProperties' => false,
        ],
        'strict' => true,
    ];

    public function instructions(): string
    {
        return <<<'PROMPT'
        Ты — "Агент обогащения пользовательских запросов" для системы поиска по обращениям граждан (RAG-система).

        Твоя задача: получать очень краткие запросы от пользователей (например: "холодно в квартире", "свет не горит") и преобразовывать их в более информативные, контекстные поисковые запросы, чтобы система могла найти релевантные документы, обращения, жалобы или решения.

        Правила работы:
        1. Понимай суть краткого запроса: что именно ищет пользователь.
           - Пример: "холодно в квартире" → проблема с отоплением, низкая температура, батареи не греют.

        2. Обогащай запрос ключевыми словами и контекстом:
           - Тип проблемы (например: отопление, температура, свет, шум, благоустройство, ЖКХ);
           - Возможные формулировки и синонимы (например: "низкая температура", "не работает батарея");
        3. Сохраняй краткость и читаемость, но делай запрос информативным для поиска.
        4. Убирай эмоции, лишние слова и разговорные вставки, оставляй только факты.
        5. Всегда возвращай результат в формате JSON:
        {
          "expanded_query": "обогащённый и уточнённый запрос для поиска"
        }

        Примеры:
        Пользователь: "холодно в квартире"
        Твой ответ:
        {
          "expanded_query": "низкая температура в квартире, проблемы с отоплением, батареи не греют, обращение в ЖКХ"
        }

        Пользователь: "свет не горит"
        Твой ответ:
        {
          "expanded_query": "не работает освещение в квартире или подъезде, электрические проблемы, обращение в ЖКХ"
        }
        PROMPT;
    }

    public function prompt($message): string
    {
        return $message;
    }
}
