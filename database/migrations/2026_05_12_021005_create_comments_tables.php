<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->unsignedBigInteger('reply_to_id')->nullable()->comment('楼中楼的目标评论');
            $table->string('guest_name', 80)->nullable();
            $table->string('guest_email', 150)->nullable();
            $table->text('content');
            $table->text('rendered_html')->nullable();
            $table->string('status', 20)->default('approved')->comment('pending/approved/spam/trash');
            $table->boolean('pinned')->default(false);
            $table->unsignedBigInteger('like_count')->default(0);
            $table->string('ip', 45)->nullable();
            $table->string('ua', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['content_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['comment_id', 'user_id']);
        });

        Schema::create('comment_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 100);
            $table->string('detail', 500)->nullable();
            $table->string('status', 20)->default('open')->comment('open/handled/dismissed');
            $table->timestamps();
            $table->index(['comment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_reports');
        Schema::dropIfExists('comment_likes');
        Schema::dropIfExists('comments');
    }
};
