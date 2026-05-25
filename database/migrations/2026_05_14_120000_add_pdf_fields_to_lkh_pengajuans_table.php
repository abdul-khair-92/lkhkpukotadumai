<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lkh_pengajuans', function (Blueprint $table) {
            $table->string('pdf_path', 500)->nullable()->after('reviewed_at');
            $table->string('document_token', 64)->nullable()->unique()->after('pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('lkh_pengajuans', function (Blueprint $table) {
            $table->dropColumn(['pdf_path', 'document_token']);
        });
    }
};
