<?php

namespace App\AiAgents\Tools;

use App\Http\Resources\CategoryTreeResource;
use App\Models\Category;
use LarAgent\Tool;

class GetChildrenCategoriesTool extends Tool
{
    protected string $name = 'get_children_categories';
    protected string $description = 'Получить дочерние категории по ID родителя';

    protected array $properties = [
        'id' => [
            'type' => 'integer',
            'description' => 'ID родительской категории',
            'enum' => [1, 27, 101, 66, 91, 119, 132, 168, 184, 197, 203, 214, 274, 279, 286, 292, 300, 306],
        ],
    ];

    protected array $required = ['parentCategoryIds'];

    public function execute(array $input): string
    {
        $categories = Category::query()
            ->select('id', 'name', 'parent_id')
            ->where('id', $input['id'])
            ->with(['children' => function ($query) {
                $query->select('id', 'name', 'parent_id');
            }])
            ->get();

        return CategoryTreeResource::collection($categories)
            ->toJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
