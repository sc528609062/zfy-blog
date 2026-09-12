<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('published');
            $table->timestamps();
        });
        Schema::create('content_topic', function (Blueprint $table) {
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->primary(['content_id', 'topic_id']);
        });
        Schema::create('invite_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedInteger('usage_limit')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->string('status')->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('user_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('status')->default('pending')->index();
            $table->text('body');
            $table->text('reply')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('content_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->timestamps();
            $table->unique(['user_id', 'content_id', 'type']);
        });
        Schema::create('user_follows', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'author_id']);
        });
    }

    public function down(): void
    {
        foreach (['user_follows', 'content_reactions', 'user_requests', 'invite_codes', 'content_topic', 'topics'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
