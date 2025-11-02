<?php

namespace App\AiAgents;

use App\AiAgents\Tools\GetChildrenCategoriesTool;
use App\AiAgents\Tools\GetParentCategoriesTool;
use App\AiAgents\Tools\WebSearchTool;
use LarAgent\Agent;

class ChatAssistantAgent extends Agent
{
    protected $history = 'file';
    protected $provider = 'default';

    protected $tools = [
        WebSearchTool::class,
        GetChildrenCategoriesTool::class,
        GetParentCategoriesTool::class,
    ];

    public function instructions(): string
    {
        return <<<'PROMPT'
        Вы — помощник, который формирует краткий, вежливый и конкретный ответ на сообщение.

        Если для ответа требуется уточнение или поиск фактической информации, сформируйте релевантный поисковый запрос и при необходимости используйте доступные инструменты (например, web_search).
        Выполняйте поиск только для того, что реально поможет в решении вопроса.
        Не выдумывай факты, отвечай по существу.
        PROMPT;
    }
}
