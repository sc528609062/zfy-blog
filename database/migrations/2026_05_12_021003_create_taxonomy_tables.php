<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->string('icon', 80)->nullable();
            $table->string('cover')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('content_count')->default(0);
            $table->json('settings')->nullable()->comment('分类级配置：默认分成比、是否必审、SEO');
            $table->timestamps();

            $table->index('parent_id');
            $table->index('sort_order');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('content_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
    }
};
