<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * users.id is UUID; l_k_h_s.user_id was foreignId (bigint), so UUIDs were coerced to wrong integers.
     */
    public function up(): void
    {
        if (! Schema::hasTable('l_k_h_s') || ! Schema::hasColumn('l_k_h_s', 'user_id')) {
            return;
        }

        $type = strtolower((string) Schema::getColumnType('l_k_h_s', 'user_id'));
        if (! in_array($type, ['bigint', 'int', 'integer'], true)) {
            return;
        }

        Schema::table('l_k_h_s', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        Schema::table('l_k_h_s', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('l_k_h_s') || ! Schema::hasColumn('l_k_h_s', 'user_id')) {
            return;
        }

        $type = strtolower((string) Schema::getColumnType('l_k_h_s', 'user_id'));
        if (in_array($type, ['bigint', 'int', 'integer'], true)) {
            return;
        }

        Schema::table('l_k_h_s', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('l_k_h_s', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });
    }
};
