<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassificationApiTest extends TestCase
{
    public function test_single_classify_validation(): void
    {
        $response = $this->postJson('/api/v1/classify', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    }

    public function test_bulk_classify_validation(): void
    {
        $response = $this->postJson('/api/v1/classify/bulk', ['items' => []]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_ai_classification_endpoint_exists(): void
    {
        // Проверяем, что маршрут существует (без авторизации должен вернуть 401)
        $response = $this->getJson('/api/v1/proposals/1/subcategory-ai');
        $response->assertStatus(401); // Ожидаем ошибку авторизации, но не 404
    }
}


