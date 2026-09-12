<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('base_fee', 12, 2)->default(0);
            $table->unsignedInteger('base_quantity')->default(1);
            $table->decimal('additional_fee', 12, 2)->default(0);
            $table->decimal('free_threshold', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_templates');
    }
};
