<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications_inbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('title', 200);
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->string('channel', 20)->default('inbox')->comment('inbox/email/wechat');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });

        Schema::create('download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('download_id')->nullable()->constrained('content_downloads')->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('ua', 500)->nullable();
            $table->string('result', 20)->default('granted')->comment('granted/denied');
            $table->string('reason', 200)->nullable();
            $table->timestamp('logged_at')->useCurrent();
            $table->index(['content_id', 'logged_at']);
            $table->index(['user_id', 'logged_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('friend_links', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('url', 500);
            $table->string('logo')->nullable();
            $table->string('description', 200)->nullable();
            $table->string('category', 50)->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friend_links');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('download_logs');
        Schema::dropIfExists('notifications_inbox');
    }
};
