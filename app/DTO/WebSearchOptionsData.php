<?php

namespace App\DTO;

use Spatie\LaravelData\Data;

class WebSearchOptionsData extends Data
{
    public function __construct(
        public ?string $topic = 'general',
        public ?string $search_depth = 'basic',
        public ?bool $include_images = false,
        public ?bool $include_answer = true,
        public ?int $max_results = 5,
        public ?bool $auto_parameters = false,
        public ?bool $include_raw_content = false,
        public ?array $include_domains = null,
        public ?array $exclude_domains = null,
        public ?string $country = 'russia',
        public ?string $time_range = null,
        public ?int $days = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public ?int $chunks_per_source = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            topic: $data['topic'] ?? 'general',
            search_depth: $data['search_depth'] ?? 'basic',
            include_images: $data['include_images'] ?? false,
            include_answer: $data['include_answer'] ?? true,
            max_results: $data['max_results'] ?? 5,
            auto_parameters: $data['auto_parameters'] ?? false,
            include_raw_content: $data['include_raw_content'] ?? false,
            include_domains: $data['include_domains'] ?? null,
            exclude_domains: $data['exclude_domains'] ?? null,
            country: $data['country'] ?? 'russia',
            time_range: $data['time_range'] ?? null,
            days: $data['days'] ?? null,
            start_date: $data['start_date'] ?? null,
            end_date: $data['end_date'] ?? null,
            chunks_per_source: $data['chunks_per_source'] ?? null,
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_filter(parent::toArray());
    }
}


