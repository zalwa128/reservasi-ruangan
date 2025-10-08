<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_schedules', function (Blueprint $table) {
            $table->dropColumn('tanggal'); // hapus kolom tanggal
        });
    }

    public function down(): void
    {
        Schema::table('fixed_schedules', function (Blueprint $table) {
            $table->date('tanggal'); // rollback kalau perlu
        });
    }
};
