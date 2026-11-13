<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin tahap lanjutan: pengaturan aturan rekomendasi, laporan hasil dari siswa,
 * dan penyuntingan katalog/konten (setara menu konten admin v14).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->json('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('result_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('test_session_id')->index();
            $table->foreign('test_session_id')->references('id')->on('test_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 32);
            $table->text('message')->nullable();
            $table->string('status', 12)->default('baru')->index(); // baru | ditinjau | selesai
            $table->text('admin_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // Kolom suntingan admin. edited_at terisi = baris dikunci dari impor ulang katalog.
        Schema::table('kbji_jobs', fn (Blueprint $t) => $t->timestamp('edited_at')->nullable());
        Schema::table('program_families', function (Blueprint $t) {
            $t->text('description')->nullable();
            $t->json('support_subjects')->nullable(); // override mapel pendukung SNBP
            $t->timestamp('edited_at')->nullable();
        });
        Schema::table('smk_programs', function (Blueprint $t) {
            $t->text('next_keywords')->nullable(); // kata kunci prodi lanjutan (satu per baris)
            $t->text('next_exclude')->nullable();
            $t->timestamp('edited_at')->nullable();
        });
        Schema::table('content_items', function (Blueprint $t) {
            $t->json('meta')->nullable();
            $t->unsignedInteger('position')->default(0);
        });

        // Arah SMA disimpan di basis data agar bisa disunting (profil skala O*NET 1–7, sama dengan tabel katalog lain).
        if (! DB::table('sma_tracks')->exists()) {
            foreach (collect(config('sma_tracks'))->except('support') as $code => $t) {
                $row = ['code' => $code, 'name' => $t['name'], 'subjects' => json_encode($t['subjects']), 'created_at' => now(), 'updated_at' => now()];
                foreach ($t['profile'] as $d => $v) {
                    $row['riasec_'.$d] = round($v * 6 + 1, 2);
                }
                arsort($t['profile']);
                $row['holland_code'] = strtoupper(implode('', array_slice(array_keys($t['profile']), 0, 3)));
                DB::table('sma_tracks')->insert($row);
            }
        }
    }

    public function down(): void
    {
        Schema::table('content_items', fn (Blueprint $t) => $t->dropColumn(['meta', 'position']));
        Schema::table('smk_programs', fn (Blueprint $t) => $t->dropColumn(['next_keywords', 'next_exclude', 'edited_at']));
        Schema::table('program_families', fn (Blueprint $t) => $t->dropColumn(['description', 'support_subjects', 'edited_at']));
        Schema::table('kbji_jobs', fn (Blueprint $t) => $t->dropColumn('edited_at'));
        Schema::dropIfExists('result_reports');
        Schema::dropIfExists('app_settings');
    }
};
