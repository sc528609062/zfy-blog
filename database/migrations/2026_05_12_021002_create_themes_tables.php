<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name');
            $table->string('version', 32);
            $table->string('author', 100)->nullable();
            $table->string('description', 500)->nullable();
            $table->string('compatible', 32)->default('^3.0');
            $table->string('preview')->nullable();
            $table->boolean('active')->default(false);
            $table->boolean('builtin')->default(false);
            $table->json('manifest')->nullable();
            $table->timestamps();
            $table->index('active');
        });

        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('theme_slug', 100);
            $table->string('key', 100);
            $table->longText('value')->nullable();
            $table->timestamps();
            $table->unique(['theme_slug', 'key']);
            $table->index('theme_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
        Schema::dropIfExists('themes');
    }
};
