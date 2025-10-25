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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->onDelete('cascade');
            $table->text('description');
            $table->string('original_name'); // Оригинальное имя файла
            $table->string('filename'); // Имя файла на диске
            $table->string('path'); // Путь к файлу
            $table->string('mime_type'); // MIME тип файла
            $table->unsignedBigInteger('size'); // Размер файла в байтах
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
