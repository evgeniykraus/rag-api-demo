<?php

namespace App\Rules;

use App\AiAgents\SentimentAnalysisAgent;
use App\Enums\TextClassificationEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

class NotMeaninglessTextRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @throws Throwable
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $response = SentimentAnalysisAgent::for(uniqid())->message($value)->respond();
        $sentiment = $response['sentiment'] ?? null;

        // Если текст классифицировался как бессмысленный
        if (mb_strtolower($sentiment) === TextClassificationEnum::meaningless->name) {
            $fail("Текст содержит бессмысленное содержание.");
        }
    }
}
