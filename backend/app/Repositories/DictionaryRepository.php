<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\City;
use Illuminate\Support\Collection;

class DictionaryRepository
{
    /**
     * @return Collection
     */
    public function cities(): Collection
    {
        return City::query()->select(['name', 'id'])->get();
    }

    /**
     * @return Collection
     */
    public function categories(): Collection
    {
        return Category::with('children.children')
            ->whereNull('parent_id')
            ->get();
    }

    /**
     * @param int $categoryId
     * @return bool
     */
    public function categoryExists(int $categoryId): bool
    {
        return Category::query()->where('id', $categoryId)->exists();
    }
}


