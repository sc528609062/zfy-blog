<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vip_levels', function (Blueprint $table) {
            $table->decimal('price_lifetime', 10, 2)->nullable();
        });
        Schema::create('vip_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained();
            $table->json('snapshot');
            $table->json('baseline')->nullable();
            $table->timestamp('applied_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_entitlements');
        Schema::table('vip_levels', fn (Blueprint $table) => $table->dropColumn('price_lifetime'));
    }
};
