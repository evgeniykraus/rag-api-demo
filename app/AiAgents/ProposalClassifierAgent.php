<?php

namespace App\AiAgents;

use App\AiAgents\Tools\GetChildrenCategoriesTool;
use App\AiAgents\Tools\GetParentCategoriesTool;
use Illuminate\Support\Collection;
use LarAgent\Agent;

class ProposalClassifierAgent extends Agent
{
    protected $history = 'in_memory';
    protected $provider = 'default';
    protected $temperature = 0.3;
    protected $tools = [
        GetParentCategoriesTool::class,
        GetChildrenCategoriesTool::class,
    ];

    protected $responseSchema = [
        'name' => 'category_id',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'ID дочерней категории',
                ],
            ],
            'required' => ['id'],
            'additionalProperties' => false,
        ],
        'strict' => true,
    ];

    public function instructions(): string
    {
        return <<<'PROMPT'
        Вы - AI агент для классификации обращений граждан.

        Ваша задача:
        1. Анализировать содержание обращения пользователя
        2. Выбирать наиболее подходящую дочернюю категорию
        3. Возвращать JSON объект с выбранной дочерней категорией

        Важно:
        1. При необходимости используй инструменты
        2. Нельзя возвращать ID родительской категории!

        Инструменты:
        - get_parent_categories - возвращает все родительские категории.
        - get_children_categories - получает ID родительской категорий, возвращает дочерние категории.


        Формат ответа - JSON объект:
        {
            \"id\": [ID дочерней категории]
        }

        Отвечайте ТОЛЬКО валидным JSON без дополнительного текста.
        PROMPT;
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

        return "Обращение пользователя: {$content}\n\nВозможные категории: {$categoriesText}";
    }
}
