<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('contents', fn (Blueprint $table) => $table->fullText(['title', 'excerpt'], 'contents_public_search'));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('contents', fn (Blueprint $table) => $table->dropFullText('contents_public_search'));
        }
    }
};
