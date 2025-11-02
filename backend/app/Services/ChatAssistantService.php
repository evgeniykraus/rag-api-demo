<?php

namespace App\Services;

use App\AiAgents\ChatAssistantAgent;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatAssistantService
{
    /**
     * @param string $sessionId
     * @param string $message
     * @return StreamedResponse
     */
    public function chat(string $sessionId, string $message): StreamedResponse
    {
        return ChatAssistantAgent::for($sessionId)->streamResponse($message, 'sse');
    }

    /**
     * @param string $sessionId
     * @return array
     */
    public function history(string $sessionId): array
    {
        $agent = ChatAssistantAgent::for($sessionId);
        $history = $agent->chatHistory();
        // Возвращаем историю в формате массива
        // Фильтруем системные сообщения, оставляем только user и assistant
        $messages = collect($history->toArray())
            ->filter(fn($message) => in_array($message['role'] ?? '', ['user', 'assistant']))
            ->values()
            ->toArray();

        return [
            'history' => $messages,
            'count' => count($messages),
        ];
    }

    /**
     * @param string $sessionId
     * @return void
     */
    public function clearHistory(string $sessionId): void
    {
        ChatAssistantAgent::for($sessionId)->clear();
    }
}


