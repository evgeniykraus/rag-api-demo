<?php

namespace App\DTO;

use App\Models\Category;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class CategoryData extends Data
{
    /**
     * @param int $id
     * @param string $title
     * @param int $parent_id
     * @param string $parent_title
     * @param int $position
     */
    public function __construct(
        public int     $id,
        public string  $title,
        public int    $parent_id,
        public string $parent_title,
        public int     $position,
    )
    {
    }

    /**
     * @param Collection $categories
     * @return Collection
     */
    public static function fromCollection(Collection $categories): Collection
    {
        return $categories->map(fn(Category $category) => new self(
            id: $category->id,
            title: $category->title,
            parent_id: $category->parent_id,
            parent_title: $category->parent->title,
            position: $category->position,
        ));
    }

    /**
     * @param Category $category
     * @return self
     */
    public static function fromModel(Category $category): self
    {
        return new self(
            id: $category->id,
            title: $category->title,
            parent_id: $category->parent_id,
            parent_title: $category->parent->title,
            position: 1,
        );
    }
}
