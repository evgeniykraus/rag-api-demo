<?php

namespace App\DTO;

use App\Models\Proposal;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class SimilarProposalData extends Data
{
    /**
     * @param int $id
     * @param string $content
     * @param int $city_id
     * @param string $city
     * @param int $category_id
     * @param string $category
     * @param float|null $similarity
     */
    public function __construct(
        public int    $id,
        public string $content,
        public int    $city_id,
        public string $city,
        public int    $category_id,
        public string $category,
        public ?float $similarity = null,
    )
    {
    }

    /**
     * @param Collection $proposals
     * @return Collection
     */
    public static function fromCollection(Collection $proposals): Collection
    {
        return $proposals->map(fn(Proposal $proposal) => new self(
            id: $proposal->id,
            content: $proposal->content,
            city_id: $proposal->city_id,
            city: $proposal->city->name,
            category_id: $proposal->category_id,
            category: $proposal->category->name,
            similarity: $proposal->similarity,
        ));
    }
}
