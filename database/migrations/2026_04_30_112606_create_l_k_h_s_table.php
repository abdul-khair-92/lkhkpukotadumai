<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('l_k_h_s', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal')->nullable();
            $table->text('kegiatan')->nullable();
            $table->text('output')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('l_k_h_s', function (Blueprint $table) {});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('l_k_h_s');
    }
};
