<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS vector;');
    }
};
