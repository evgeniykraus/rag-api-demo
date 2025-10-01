<?php

namespace App\AiAgents;

use App\AiAgents\Tools\WebSearchTool;
use LarAgent\Agent;

class WebSearchAgent extends Agent
{
//    protected $model = 'meta-llama-3-8b-instruct';
    protected $model = 'gpt-5-mini';
    protected $history = 'in_memory';
    protected $provider = 'default';
    protected $temperature = 0.7;
    protected $tools = [
        WebSearchTool::class,
    ];

    public function instructions(): string
    {
        return "Ты вежливо отвечаешь на вопросы пользователя.";
    }
}


