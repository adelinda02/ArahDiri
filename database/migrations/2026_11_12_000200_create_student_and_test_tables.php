<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('school_name', 120)->nullable();
            // Khusus mahasiswa UMB (pengakuan pengguna, tanpa verifikasi)
            $table->foreignId('umb_program_offering_id')->nullable()->constrained('program_offerings')->nullOnDelete();
            $table->unsignedTinyInteger('umb_semester')->nullable();
            $table->boolean('umb_self_declared')->default(false);
            $table->string('avatar', 24)->nullable();
            $table->string('bio', 160)->nullable();
            $table->timestamps();
        });

        Schema::create('test_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('level_at_test', 10);
            $table->string('status', 16)->default('in_progress'); // in_progress | completed | abandoned
            $table->string('instrument_version', 32);
            $table->unsignedTinyInteger('current_item')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('test_answers', function (Blueprint $table) {
            $table->id();
            $table->uuid('test_session_id');
            $table->unsignedTinyInteger('item_no'); // 1-50
            $table->unsignedTinyInteger('value'); // 1-5
            $table->timestamps();
            $table->unique(['test_session_id', 'item_no']);
            $table->foreign('test_session_id')->references('id')->on('test_sessions')->cascadeOnDelete();
        });

        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('test_session_id')->unique();
            foreach (['r', 'i', 'a', 's', 'e', 'c'] as $d) {
                $table->unsignedSmallInteger('score_'.$d);
            }
            foreach (['r', 'i', 'a', 's', 'e', 'c'] as $d) {
                $table->decimal('norm_'.$d, 5, 4);
            }
            $table->string('holland_code', 3)->index();
            $table->string('scoring_version', 32);
            $table->timestamps();
            $table->foreign('test_session_id')->references('id')->on('test_sessions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_results');
        Schema::dropIfExists('test_answers');
        Schema::dropIfExists('test_sessions');
        Schema::dropIfExists('student_profiles');
    }
};
