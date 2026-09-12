<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extension_event_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('consumer', 80);
            $table->string('event_key', 160);
            $table->timestamp('consumed_at');
            $table->unique(['consumer', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_event_receipts');
    }
};
