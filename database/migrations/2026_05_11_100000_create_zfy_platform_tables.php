<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->string('avatar_url')->nullable()->after('password');
            $table->text('bio')->nullable()->after('avatar_url');
            $table->boolean('is_author')->default(false)->after('bio');
            $table->string('author_status')->default('none')->after('is_author');
            $table->json('meta')->nullable()->after('remember_token');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->boolean('autoload')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('mixed');
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('status')->default('draft')->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('cover_url')->nullable();
            $table->json('block_json')->nullable();
            $table->longText('rendered_html')->nullable();
            $table->longText('markdown_cache')->nullable();
            $table->json('seo')->nullable();
            $table->json('pricing')->nullable();
            $table->json('access_rules')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['type', 'status', 'published_at']);
        });

        Schema::create('content_tag', function (Blueprint $table) {
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['content_id', 'tag_id']);
        });

        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('name');
            $table->string('path')->nullable();
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk')->default('public');
            $table->string('type')->index();
            $table->string('name');
            $table->string('path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('role')->default('attachment');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->text('body');
            $table->string('ip_address')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('vip_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('level')->default(1);
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->unsignedInteger('discount_percent')->default(100);
            $table->decimal('fixed_discount', 10, 2)->default(0);
            $table->json('benefits')->nullable();
            $table->timestamps();
        });

        Schema::create('user_vips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vip_level_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('frozen_balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('remark')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('points_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('points')->default(0);
            $table->integer('frozen_points')->default(0);
            $table->timestamps();
        });

        Schema::create('points_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('points_account_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->integer('points');
            $table->integer('balance_after')->default(0);
            $table->string('remark')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_no')->unique();
            $table->string('type')->index();
            $table->string('status')->default('pending')->index();
            $table->string('pay_channel')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('buyer_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('item_type');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('title');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway');
            $table->string('status')->default('pending')->index();
            $table->string('trade_no')->nullable()->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway');
            $table->string('event');
            $table->string('status')->default('received');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('device_hash')->nullable();
            $table->string('status')->default('allowed');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('points_store_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->integer('points_price');
            $table->integer('stock')->default(-1);
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('points_exchange_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('points_store_item_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->integer('points_spent');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('author_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->string('status')->default('pending');
            $table->decimal('share_percent', 5, 2)->default(50);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('author_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('author_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->string('method')->nullable();
            $table->json('account_snapshot')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('author_settlement_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('share_percent', 5, 2)->default(50);
            $table->boolean('is_default')->default(false);
            $table->json('conditions')->nullable();
            $table->timestamps();
        });

        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('version')->default('1.0.0');
            $table->string('author')->default('zfy-blog');
            $table->string('compatible')->default('^1.0');
            $table->string('entry_view');
            $table->string('preview')->nullable();
            $table->json('menus')->nullable();
            $table->json('regions')->nullable();
            $table->json('settings_schema')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->string('scope')->default('global');
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['theme_id', 'scope', 'key']);
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('title');
            $table->string('url');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('page_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('scope')->unique();
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->json('schema');
            $table->string('status')->default('published');
            $table->timestamps();
        });

        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->string('region')->index();
            $table->string('type');
            $table->string('title')->nullable();
            $table->json('config')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('version')->default('1.0.0');
            $table->string('provider')->nullable();
            $table->json('permissions')->nullable();
            $table->json('events')->nullable();
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('plugin_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['plugin_id', 'key']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['notification_id', 'user_id']);
        });

        Schema::create('system_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('status')->default('installed');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('upgrade_logs', function (Blueprint $table) {
            $table->id();
            $table->string('from_version')->nullable();
            $table->string('to_version');
            $table->string('status')->default('pending');
            $table->longText('log')->nullable();
            $table->timestamps();
        });

        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('severity')->default('info');
            $table->string('ip_address')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'security_events', 'upgrade_logs', 'system_versions', 'notification_reads', 'notifications',
            'plugin_settings', 'plugins', 'widgets', 'page_layouts', 'menu_items', 'menus',
            'theme_settings', 'themes', 'author_settlement_rules', 'author_withdrawals', 'author_earnings',
            'author_profiles', 'points_exchange_orders', 'points_store_items', 'download_logs',
            'payment_logs', 'payments', 'order_items', 'orders', 'points_transactions', 'points_accounts',
            'wallet_transactions', 'wallets', 'user_vips', 'vip_levels', 'comments', 'attachments',
            'media', 'media_folders', 'content_tag', 'contents', 'tags', 'categories', 'settings',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'avatar_url', 'bio', 'is_author', 'author_status', 'meta']);
        });
    }
};
