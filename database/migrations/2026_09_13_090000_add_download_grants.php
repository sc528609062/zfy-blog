<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_grants', function (Blueprint $table) {
            $table->id();
            $table->char('token_hash', 64)->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('attachment_id');
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_grants');
    }
};
