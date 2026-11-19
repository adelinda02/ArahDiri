<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jenjang "Mahasiswa UMB" menjadi "Mahasiswa" (kampus mana pun).
 * Kolom prodi UMB di profil menjadi prodi yang sedang dijalani.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('level', 'mhs_umb')->update(['level' => 'mhs']);
        DB::table('test_sessions')->where('level_at_test', 'mhs_umb')->update(['level_at_test' => 'mhs']);

        Schema::table('student_profiles', function ($table) {
            $table->renameColumn('umb_program_offering_id', 'current_program_offering_id');
            $table->renameColumn('umb_semester', 'current_semester');
            $table->renameColumn('umb_self_declared', 'current_self_declared');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function ($table) {
            $table->renameColumn('current_program_offering_id', 'umb_program_offering_id');
            $table->renameColumn('current_semester', 'umb_semester');
            $table->renameColumn('current_self_declared', 'umb_self_declared');
        });

        DB::table('users')->where('level', 'mhs')->update(['level' => 'mhs_umb']);
        DB::table('test_sessions')->where('level_at_test', 'mhs')->update(['level_at_test' => 'mhs_umb']);
    }
};
