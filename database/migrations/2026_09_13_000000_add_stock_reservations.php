<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('stock_item_id')->nullable()->constrained('stock_items')->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('status')->default('reserved')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
        Schema::table('product_variants', fn (Blueprint $table) => $table->unsignedInteger('reserved')->default(0));
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
        Schema::table('product_variants', fn (Blueprint $table) => $table->dropColumn('reserved'));
    }
};
