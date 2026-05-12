<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 64)->nullable()->unique()->after('id');
            $table->string('phone', 32)->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('cover')->nullable()->after('avatar');
            $table->string('bio', 500)->nullable()->after('cover');
            $table->string('website')->nullable()->after('bio');
            $table->string('location', 100)->nullable()->after('website');
            $table->string('status', 20)->default('active')->after('location')->comment('active/banned/pending');
            $table->timestamp('banned_at')->nullable()->after('status');
            $table->string('ban_reason')->nullable()->after('banned_at');
            $table->timestamp('last_login_at')->nullable()->after('ban_reason');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->json('preferences')->nullable()->after('last_login_ip');

            $table->index('status');
            $table->index('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['last_login_at']);
            $table->dropColumn([
                'username', 'phone', 'avatar', 'cover', 'bio', 'website',
                'location', 'status', 'banned_at', 'ban_reason',
                'last_login_at', 'last_login_ip', 'preferences',
            ]);
        });
    }
};
