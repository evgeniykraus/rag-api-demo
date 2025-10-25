<?php

namespace App\DTO;

use Spatie\LaravelData\Data;

class CategoryVectorConfigData extends Data
{
    public function __construct(
        public float $threshold,
        public float $outlier_cosine_threshold
    ) {}

    /**
     * Загрузить конфиг из файла config/categories.php.
     * @return self
     */
    public static function fromConfig(): self
    {
        $cfg = config('categories.vector');

        return new self(
            threshold: (float) $cfg['threshold'],
            outlier_cosine_threshold: (float) $cfg['outlier_cosine_threshold']
        );
    }
}
