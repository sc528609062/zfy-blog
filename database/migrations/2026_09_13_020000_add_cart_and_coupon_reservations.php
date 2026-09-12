<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->default(0);
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['user_id', 'product_id', 'variant_id']);
        });
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('recipient', 100);
            $table->string('phone', 30);
            $table->string('region', 200);
            $table->string('address', 500);
            $table->string('postal_code', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        Schema::create('checkout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('request_key');
            $table->foreignId('order_id')->constrained();
            $table->timestamps();
            $table->unique(['user_id', 'request_key']);
        });
        Schema::create('coupon_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('reserved');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_reservations');
        Schema::dropIfExists('checkout_requests');
        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('cart_items');
    }
};
