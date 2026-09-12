<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('author_settlement_rules', function (Blueprint $table) {
            $table->string('scope')->default('default');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->unsignedInteger('hold_days')->default(0);
        });
        Schema::table('author_earnings', function (Blueprint $table) {
            $table->timestamp('available_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('author_earnings', fn (Blueprint $table) => $table->dropColumn('available_at'));
        Schema::table('author_settlement_rules', fn (Blueprint $table) => $table->dropColumn(['scope', 'target_id', 'hold_days']));
    }
};
