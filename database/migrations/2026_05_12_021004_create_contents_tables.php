<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index()->comment('post/images/files/page');
            $table->string('status', 20)->default('draft')->index()
                ->comment('draft/pending/published/private/scheduled/rejected/trash');
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('excerpt', 500)->nullable();
            $table->unsignedBigInteger('cover_media_id')->nullable();
            $table->string('cover_url')->nullable();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            // 编辑器三表征
            $table->longText('block_json')->nullable()->comment('TipTap block JSON (权威源)');
            $table->longText('rendered_html')->nullable()->comment('服务端渲染缓存 HTML');
            $table->longText('markdown_cache')->nullable();
            $table->longText('plain_text')->nullable()->comment('搜索用纯文本');

            // 可见性与价格
            $table->string('visibility', 20)->default('public')
                ->comment('public/logged_in/vip/paid/commented/password/points');
            $table->string('access_password', 100)->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('points_price')->default(0);
            $table->string('vip_discount_type', 20)->nullable()->comment('rate/amount');
            $table->decimal('vip_discount_value', 10, 4)->nullable();
            $table->foreignId('required_vip_level_id')->nullable();

            // 行为策略
            $table->string('comment_policy', 20)->default('open')->comment('open/closed/login/audit');
            $table->string('download_policy', 20)->default('open')->comment('open/login/vip/paid/points');

            // 计数（去标准化）
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('like_count')->default(0);
            $table->unsignedBigInteger('comment_count')->default(0);
            $table->unsignedBigInteger('favorite_count')->default(0);
            $table->unsignedBigInteger('download_count')->default(0);

            // SEO
            $table->string('seo_title', 255)->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('seo_keywords', 500)->nullable();

            // 时间
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status', 'published_at']);
            $table->index(['author_id', 'status']);
            $table->fullText(['title', 'plain_text']);
        });

        Schema::create('content_tag', function (Blueprint $table) {
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['content_id', 'tag_id']);
            $table->index('tag_id');
        });

        Schema::create('content_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->unsignedBigInteger('media_id')->nullable();
            $table->string('url');
            $table->string('title', 200)->nullable();
            $table->string('caption', 500)->nullable();
            $table->string('alt', 200)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('allow_original_download')->default(false);
            $table->timestamps();
            $table->index(['content_id', 'sort_order']);
        });

        Schema::create('content_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('label', 100)->comment('显示名称：高速下载/百度网盘/夸克网盘 等');
            $table->string('platform', 50)->default('local')
                ->comment('local/baidu/quark/aliyun/external/url');
            $table->string('url', 1000)->nullable();
            $table->string('extract_code', 50)->nullable();
            $table->string('unzip_password', 50)->nullable();
            $table->string('version', 50)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('note', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->index(['content_id', 'sort_order']);
        });

        Schema::create('content_access_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('rule_type', 30)->comment('vip_level/buy/comment/password/points/login');
            $table->json('config')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->index(['content_id', 'rule_type']);
        });

        Schema::create('content_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->unsignedInteger('paid_points')->default(0);
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'content_id']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'content_id']);
        });

        Schema::create('content_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'content_id']);
        });

        Schema::create('content_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('ua', 500)->nullable();
            $table->timestamp('viewed_at')->useCurrent();
            $table->index(['content_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_views');
        Schema::dropIfExists('content_likes');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('content_purchases');
        Schema::dropIfExists('content_access_rules');
        Schema::dropIfExists('content_downloads');
        Schema::dropIfExists('content_images');
        Schema::dropIfExists('content_tag');
        Schema::dropIfExists('contents');
    }
};
