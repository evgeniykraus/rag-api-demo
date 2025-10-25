<?php

namespace App\Support;

use MathPHP\Exception\BadDataException;
use MathPHP\LinearAlgebra\Vector as MathVector;
use RuntimeException;

class VectorMath
{
    /**
     * L2-нормализация вектора. Возвращает вектор нулей при почти нулевой норме.
     *
     * @param array $v
     * @return array
     * @throws BadDataException
     */
    public static function l2normalize(array $v): array
    {
        if (empty($v)) {
            return [];
        }
        $vector = new MathVector(array_map('floatval', $v));
        $norm = $vector->l2Norm();
        if ($norm <= 1e-12) {
            return array_fill(0, count($v), 0.0);
        }
        $normalized = $vector->normalize()->getVector();
        $expectedDim = (int) config('embeddings.dimension', 0);
        if ($expectedDim > 0 && count($normalized) !== $expectedDim) {
            throw new RuntimeException(sprintf(__('exceptions.embedding.unexpected_dimension'), count($normalized), $expectedDim));
        }
        return $normalized;
    }

    /**
     * Применить L2-нормализацию ко всем векторам массива.
     *
     * @param array $vectors
     * @return array
     */
    public static function l2normalizeEach(array $vectors): array
    {
        return array_map([self::class, 'l2normalize'], $vectors);
    }
}


