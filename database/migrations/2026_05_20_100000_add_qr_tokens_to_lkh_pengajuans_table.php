<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lkh_pengajuans', function (Blueprint $table) {
            $table->string('qr_pengaju_token', 64)->nullable()->unique()->after('document_token');
            $table->string('qr_penyetuju_token', 64)->nullable()->unique()->after('qr_pengaju_token');
        });
    }

    public function down(): void
    {
        Schema::table('lkh_pengajuans', function (Blueprint $table) {
            $table->dropColumn(['qr_pengaju_token', 'qr_penyetuju_token']);
        });
    }
};
