<?php

namespace App\DTO;

use App\Models\CategoryVector;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class CategoryVectorData extends Data
{
    /**
     * @param int $id
     * @param string $title
     * @param int $parent_id
     * @param string $parent_title
     * @param string $intent_confidence
     * @param int $position
     */
    public function __construct(
        public int    $id,
        public string $title,
        public int    $parent_id,
        public string $parent_title,
        public string $intent_confidence,
        public int    $position,
    )
    {
    }

    /**
     * @param Collection $categoryVectors
     * @return Collection
     */
    public static function fromCollection(Collection $categoryVectors): Collection
    {
        return $categoryVectors->map(fn(CategoryVector $categoryVector, int $index) => new self(
            id: $categoryVector->category_id,
            title: $categoryVector->title,
            parent_id: $categoryVector->parent_category_id,
            parent_title: $categoryVector->parent_category_title,
            intent_confidence: $categoryVector->intent_confidence,
            position: $index + 1,
        ));
    }
}
