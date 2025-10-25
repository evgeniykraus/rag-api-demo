<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proposal_metadata', function (Blueprint $table) {
            $table->id();
            
            // Связь с обращением
            $table->foreignId('proposal_id')->constrained()->onDelete('cascade');
            $table->index('proposal_id');
            
            // === CORRECTNESS AGENT (Агент корректности) ===
            // Оценка соответствия ответа сути обращения (0.0-1.0)
            $table->decimal('correctness_score', 3, 2)->nullable()->comment('Оценка соответствия ответа сути обращения (0.0-1.0)');
            
            // Оценка полноты ответа, все ли ключевые пункты запроса покрыты (0.0-1.0)
            $table->decimal('completeness_score', 3, 2)->nullable()->comment('Оценка полноты ответа, все ли ключевые пункты запроса покрыты (0.0-1.0)');
            
            // Оценка наличия конкретных шагов, сроков, контактов в ответе (0.0-1.0)
            $table->decimal('actionable_score', 3, 2)->nullable()->comment('Оценка наличия конкретных шагов, сроков, контактов в ответе (0.0-1.0)');
            
            // Список ключевых пунктов обращения, которые не были покрыты в ответе
            $table->json('missing_points')->nullable()->comment('Список ключевых пунктов обращения, которые не были покрыты в ответе');
            
            // === TONE & CLARITY AGENT (Агент тона и ясности) ===
            // Оценка вежливости и доброжелательности тона ответа (0.0-1.0)
            $table->decimal('tone_politeness_score', 3, 2)->nullable()->comment('Оценка вежливости и доброжелательности тона ответа (0.0-1.0)');
            
            // Оценка ясности и простоты формулировок ответа (0.0-1.0)
            $table->decimal('clarity_score', 3, 2)->nullable()->comment('Оценка ясности и простоты формулировок ответа (0.0-1.0)');
            
            // Флаг, указывающий, перегружен ли ответ канцеляритом или жаргоном
            $table->boolean('jargon_flag')->nullable()->comment('Флаг, указывающий, перегружен ли ответ канцеляритом или жаргоном');
            
            // === COMPLIANCE AGENT (Агент соответствия правилам) ===
            // Оценка соблюдения базовых правил и регламентов в ответе (0.0-1.0)
            $table->decimal('policy_compliance_score', 3, 2)->nullable()->comment('Оценка соблюдения базовых правил и регламентов в ответе (0.0-1.0)');
            
            // Флаги потенциальных нарушений (personal_data, legal_risk, incorrect_commitment, none)
            $table->json('risk_flags')->nullable()->comment('Флаги потенциальных нарушений (personal_data, legal_risk, incorrect_commitment, none)');
            
            // === ENTITIES & TAGS AGENT (Агент сущностей и тегов) ===
            // Краткие теги тематики обращения и ответа (для фильтров и отчётов)
            $table->json('intent_tags')->nullable()->comment('Краткие теги тематики обращения и ответа (для фильтров и отчётов)');
            
            // Извлеченные локации (улица, адрес, объект) из текста обращения и ответа
            $table->json('entities_locations')->nullable()->comment('Извлеченные локации (улица, адрес, объект) из текста обращения и ответа');
            
            // Извлеченные объекты (дорога, освещение, ТКО и пр.) из текста обращения и ответа
            $table->json('entities_objects')->nullable()->comment('Извлеченные объекты (дорога, освещение, ТКО и пр.) из текста обращения и ответа');
            
            // === RESOLUTION AGENT (Агент разрешения) ===
            // Вероятность, что заявитель сочтёт ответ достаточным и обращение будет закрыто (0.0-1.0)
            $table->decimal('resolution_likelihood', 3, 2)->nullable()->comment('Вероятность, что заявитель сочтёт ответ достаточным и обращение будет закрыто (0.0-1.0)');
            
            // Флаг, указывающий, требуется ли уточнение или дополнительная информация от заявителя
            $table->boolean('followup_needed')->nullable()->comment('Флаг, указывающий, требуется ли уточнение или дополнительная информация от заявителя');
            
            // Краткий список последующих шагов, если они необходимы для решения обращения
            $table->json('next_steps')->nullable()->comment('Краткий список последующих шагов, если они необходимы для решения обращения');
            
            // === СИСТЕМНЫЕ ПОЛЯ ===
            // Статус обработки метаданных (pending, processing, completed, failed)
            $table->string('status')->default('pending')->comment('Статус обработки метаданных (pending, processing, completed, failed)');
            
            // Ошибка при обработке, если статус failed
            $table->text('error_message')->nullable()->comment('Ошибка при обработке, если статус failed');
            
            // Время начала обработки
            $table->timestamp('processed_at')->nullable()->comment('Время начала обработки');
            
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['status', 'processed_at']);
            $table->index('resolution_likelihood');
            $table->index('followup_needed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_metadata');
    }
};
