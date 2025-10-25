<?php

namespace App\AiAgents\Tools;

use App\DTO\WebSearchOptionsData;
use App\Services\WebSearchService;
use LarAgent\Tool;

class WebSearchTool extends Tool
{
    protected string $name = 'web_search';

    protected string $description = 'Search web search';

    protected array $properties = [
        'query' => [
            'type' => 'string',
            'description' => 'Search query'
        ],
        'topic' => [
            'type' => 'string',
            'description' => 'Search topic',
            'enum' => ['general', 'news'],
        ],
        'search_depth' => [
            'type' => 'string',
            'description' => 'Search depth',
            'enum' => ['basic', 'advanced'],
        ],
        'include_answer' => [
            'type' => 'boolean',
            'description' => 'Include answer',
        ],
        'max_results' => [
            'type' => 'integer',
            'description' => 'The maximum number of results',
        ],
    ];

    protected array $required = ['query'];

    public function execute(array $input): array
    {
        $query = $input['query'];
        return WebSearchService::search($query, WebSearchOptionsData::fromArray($input));
    }
}
