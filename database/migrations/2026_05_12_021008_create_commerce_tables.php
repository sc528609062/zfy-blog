<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 商业化基础表（VIP/订单/支付/钱包/积分/作者收益）。
 *
 * Sprint 1 仅建表，Sprint 3-5 完整接入业务逻辑。
 */
return new class extends Migration {
    public function up(): void
    {
        // === VIP ===
        Schema::create('vip_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('duration_days')->nullable()->comment('null = 永久');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('discount_rate', 5, 4)->nullable()->comment('0.9 = 9 折');
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->unsignedInteger('download_limit_daily')->nullable();
            $table->json('benefits')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('color', 20)->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
            $table->index(['enabled', 'sort_order']);
        });

        Schema::create('user_vips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vip_level_id')->constrained('vip_levels');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('source', 30)->default('purchase')
                ->comment('purchase/manual/gift/code');
            $table->unsignedBigInteger('source_order_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['user_id', 'active']);
            $table->index('expires_at');
        });

        // === 订单 ===
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 64)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 32)->index()
                ->comment('vip_purchase/content_purchase/wallet_recharge/points_purchase/points_exchange/author_withdrawal');
            $table->string('status', 20)->default('pending')->index()
                ->comment('pending/paid/closed/refunded/failed/cancelled');
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('wallet_used', 12, 2)->default(0);
            $table->unsignedInteger('points_used')->default(0);
            $table->string('currency', 8)->default('CNY');
            $table->string('gateway', 32)->nullable();
            $table->string('gateway_trade_no', 100)->nullable();
            $table->string('subject', 200)->nullable();
            $table->json('meta')->nullable();
            $table->json('snapshot')->nullable()->comment('订单快照：内容标题、价格等');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
            $table->index(['type', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('item_type', 32)->comment('content/vip_level/points_item');
            $table->unsignedBigInteger('item_id');
            $table->string('title', 200);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['item_type', 'item_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('gateway', 32);
            $table->string('gateway_trade_no', 100)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending')->index()
                ->comment('pending/success/failed/refunded');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['gateway', 'gateway_trade_no']);
        });

        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('event', 64);
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('logged_at')->useCurrent();
            $table->index(['order_id', 'event']);
        });

        // === 钱包 ===
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('balance', 14, 2)->default(0);
            $table->decimal('frozen', 14, 2)->default(0);
            $table->decimal('total_recharged', 14, 2)->default(0);
            $table->decimal('total_spent', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 32)
                ->comment('recharge/spend/refund/adjustment/freeze/unfreeze');
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('subject', 200)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        // === 积分 ===
        Schema::create('points_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('balance')->default(0);
            $table->unsignedBigInteger('total_earned')->default(0);
            $table->unsignedBigInteger('total_spent')->default(0);
            $table->timestamps();
        });

        Schema::create('points_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 32)
                ->comment('earn/spend/exchange/adjustment');
            $table->string('source', 50)
                ->comment('sign_in/comment/submit_post/publish_post/buy_content/buy_vip/exchange/admin');
            $table->integer('amount');
            $table->bigInteger('balance_after');
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('related_type', 100)->nullable();
            $table->string('note', 200)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('points_store_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('description', 500)->nullable();
            $table->string('cover')->nullable();
            $table->string('reward_type', 32)
                ->comment('content/vip_days/wallet_amount/coupon/physical');
            $table->json('reward_payload')->nullable();
            $table->unsignedInteger('points_cost');
            $table->unsignedInteger('stock')->nullable();
            $table->unsignedInteger('per_user_limit')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['enabled', 'sort_order']);
        });

        Schema::create('points_exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('points_store_items');
            $table->unsignedInteger('points_cost');
            $table->string('status', 20)->default('pending')
                ->comment('pending/delivered/failed/refunded');
            $table->json('delivery_payload')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        // === 作者收益 ===
        Schema::create('author_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('content_id')->nullable()->constrained('contents')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('share_rate', 5, 4);
            $table->decimal('net_amount', 12, 2);
            $table->string('status', 20)->default('pending')
                ->comment('pending/available/withdrawing/paid/cancelled');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->index(['author_id', 'status']);
        });

        Schema::create('author_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('withdrawal_no', 64)->unique();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 30)->default('manual')->comment('manual/alipay/wechat/bank');
            $table->json('payout_info')->nullable();
            $table->string('status', 20)->default('pending')
                ->comment('pending/approved/paid/rejected/cancelled');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('reject_reason', 500)->nullable();
            $table->string('voucher', 500)->nullable()->comment('打款凭证截图/单号');
            $table->timestamps();
            $table->index(['author_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_withdrawals');
        Schema::dropIfExists('author_earnings');
        Schema::dropIfExists('points_exchanges');
        Schema::dropIfExists('points_store_items');
        Schema::dropIfExists('points_transactions');
        Schema::dropIfExists('points_accounts');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('payment_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('user_vips');
        Schema::dropIfExists('vip_levels');
    }
};
