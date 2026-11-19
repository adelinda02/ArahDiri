<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jawaban mahasiswa atas pertanyaan awal "Apakah saat ini kamu merasa salah jurusan?".
 * null = belum menjawab (diminta menjawab dulu di halaman kampus & prodi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->boolean('feels_mismatch')->nullable()->after('current_self_declared');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn('feels_mismatch');
        });
    }
};
