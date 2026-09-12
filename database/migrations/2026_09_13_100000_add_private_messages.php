<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('request_id');
            $table->text('body');
            $table->string('status', 20)->default('sent');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['sender_id', 'request_id']);
            $table->index(['recipient_id', 'status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_messages');
    }
};
