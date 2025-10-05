<?php

namespace App\AiAgents;

use Illuminate\Support\Collection;
use LarAgent\Agent;

class ProposalClassifierAgent extends Agent
{
    protected $history = 'in_memory';
    protected $provider = 'default';
    protected $temperature = 0.3;

    protected $responseSchema = [
        'name' => 'category_id',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'ID категории',
                ],
            ],
            'required' => ['id'],
            'additionalProperties' => false,
        ],
        'strict' => true,
    ];

    public function instructions(): string
    {
        return __('prompts.category_classification_prompt');
    }

    public static function buildMessage(string $content, Collection $categories): string
    {
        $categoriesText = $categories
            ->groupBy(fn($category) => $category->parent->name)
            ->map(function (Collection $subcategories, $parentName) {
                $subArray = $subcategories->map(fn($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                ])->toArray();

                // Формируем текст для родителя
                return "Категория: {$parentName}:\n" . json_encode($subArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            })
            ->implode("\n\n");

        return "Обращение пользователя: {$content}\n\n{$categoriesText}";
    }
}
