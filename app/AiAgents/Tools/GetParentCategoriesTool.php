<?php

namespace App\AiAgents\Tools;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use LarAgent\Tool;

class GetParentCategoriesTool extends Tool
{
    protected string $name = 'get_parent_categories';
    protected string $description = 'Получить родительские категории';

    public function execute(array $input): string
    {
        $categories = Category::query()
            ->select('id', 'name')
            ->whereNull('parent_id')
            ->get();

        return CategoryResource::collection($categories)
            ->toJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
