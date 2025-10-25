<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('proposal_vectors', function (Blueprint $table) {
            $table->unsignedBigInteger('proposal_id')->primary();
            $table->vector('embedding', (int) config('embeddings.dimension', 1024));

            $table->foreign('proposal_id')->references('id')->on('proposals')->onDelete('cascade');
        });
        DB::statement('CREATE INDEX IF NOT EXISTS proposal_vectors_embedding_ivfflat ON proposal_vectors USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_vectors');
    }
};


