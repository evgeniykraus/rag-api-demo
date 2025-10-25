<?php

namespace App\AiAgents;

use App\AiAgents\Tools\WebSearchTool;
use App\Models\Proposal;
use Illuminate\Support\Collection;
use LarAgent\Agent;

class ProposalResponseGeneratorAgent extends Agent
{
    protected $history = 'in_memory';

    protected $provider = 'default';

    protected $tools = [
        WebSearchTool::class,
    ];

    public function instructions(): string
    {
        return <<<'PROMPT'
        Вы — помощник, который формирует краткий, вежливый и конкретный ответ на обращение гражданина.

        Если для ответа требуется уточнение или поиск фактической информации, сформируйте релевантный поисковый запрос и при необходимости используйте доступные инструменты (например, web_search).
        Выполняйте поиск только для того, что реально поможет в решении вопроса.
        Если поиск не дал полезной информации, игнорируй его результаты.
        Не выдумывай факты, отвечай по существу.
        PROMPT;
    }

    public static function buildMessage(Proposal $proposal, Collection $similarResponses): string
    {
        $examples = $similarResponses
            ->pluck('content')
            ->map(fn(string $item, int $index) => "Пример " . ($index + 1) . ":\n" . $item)
            ->implode("\n\n");

        return "{$examples} \n\nОбращение пользователя:\n{$proposal->content}";
    }
}
