<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('author_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('display_name', 100);
            $table->string('contact', 100)->nullable();
            $table->string('direction', 200)->nullable()->comment('创作方向');
            $table->json('payout_info')->nullable()->comment('收款信息：bank/alipay/wechat 等');
            $table->text('apply_reason')->nullable();
            $table->string('status', 20)->default('pending')
                ->comment('pending/approved/rejected/banned');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reject_reason', 500)->nullable();
            $table->decimal('settlement_rate', 5, 4)->nullable()->comment('个人分成比');
            $table->boolean('skip_review')->default(false);
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('username', 100)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('ua', 500)->nullable();
            $table->string('result', 20)->comment('success/failed');
            $table->string('reason', 200)->nullable();
            $table->timestamp('attempted_at')->useCurrent();
            $table->index(['user_id', 'attempted_at']);
            $table->index('ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('author_profiles');
    }
};
