<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Indeks untuk mesin rekomendasi (tahap 3). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_offerings', function (Blueprint $table) {
            $table->index(['program_family_id', 'is_active'], 'po_family_active_idx');
            $table->index(['campus_id', 'level'], 'po_campus_level_idx');
        });
        Schema::table('program_family_jobs', function (Blueprint $table) {
            $table->index(['program_family_id', 'rank'], 'pfj_family_rank_idx');
        });
        Schema::table('accreditations', function (Blueprint $table) {
            $table->index(['subject_type', 'rating_score'], 'acc_type_score_idx');
        });
    }

    public function down(): void
    {
        Schema::table('program_offerings', function (Blueprint $table) {
            $table->dropIndex('po_family_active_idx');
            $table->dropIndex('po_campus_level_idx');
        });
        Schema::table('program_family_jobs', fn (Blueprint $table) => $table->dropIndex('pfj_family_rank_idx'));
        Schema::table('accreditations', fn (Blueprint $table) => $table->dropIndex('acc_type_score_idx'));
    }
};
