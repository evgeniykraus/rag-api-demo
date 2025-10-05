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
        return __('agent_instructions.response_generator');
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
