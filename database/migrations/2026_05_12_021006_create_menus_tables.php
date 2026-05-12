<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('location', 100)->unique()
                ->comment('主题声明的位置：primary/mobile/footer 等');
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('label', 100);
            $table->string('icon', 80)->nullable();
            $table->string('target_type', 30)->default('url')
                ->comment('url/content/category/tag/page/route');
            $table->string('target_ref', 500)->nullable();
            $table->string('open_in', 20)->default('_self')->comment('_self/_blank');
            $table->string('visibility', 30)->default('all')
                ->comment('all/guest/auth/vip/role:xxx');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->index(['menu_id', 'parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
