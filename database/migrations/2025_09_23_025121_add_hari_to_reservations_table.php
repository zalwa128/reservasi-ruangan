<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('day_of_week')->after('tanggal')->nullable();

            // Ubah waktu_mulai & waktu_selesai ke tipe time (biar konsisten)
            $table->time('start_time')->change();
            $table->time('end_time')->change();
        });
    }

    public function down()
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('day_of_week');

            $table->string('start_time')->change();
            $table->string('end_time')->change();
        });
    }
};
