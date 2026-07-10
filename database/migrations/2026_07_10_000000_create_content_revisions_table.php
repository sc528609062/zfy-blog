<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 24)->default('revision')->index();
            $table->uuid('draft_key')->nullable()->index();
            $table->json('snapshot');
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamps();

            $table->index(['content_id', 'kind', 'created_at']);
            $table->index(['user_id', 'draft_key', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_revisions');
    }
};
