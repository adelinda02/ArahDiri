<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->uuid('test_session_id')->index();
            // sma_track | smk_program | program | umb_program | umb_eval | career
            $table->string('kind', 16);
            $table->unsignedTinyInteger('rank');
            $table->string('target_type', 32); // program_family | smk_program | sma_track | kbji_job | program_offering
            $table->string('target_id', 64);
            $table->decimal('score', 6, 4);
            $table->json('reason')->nullable();
            $table->string('engine_version', 32);
            $table->timestamps();
            $table->unique(['test_session_id', 'kind', 'rank']);
            $table->foreign('test_session_id')->references('id')->on('test_sessions')->cascadeOnDelete();
        });

        Schema::create('recommendation_campuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommendation_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_offering_id')->nullable()->constrained()->nullOnDelete();
            $table->json('program_accreditation')->nullable(); // salinan saat rekomendasi dibuat
            $table->json('campus_accreditation')->nullable();
            $table->timestamp('accreditation_fetched_at')->nullable();
            $table->string('status', 24)->default('ready'); // ready | pending_accreditation
            $table->timestamps();
        });

        Schema::create('umb_promotions', function (Blueprint $table) {
            $table->id();
            $table->uuid('test_session_id')->index();
            $table->foreignId('recommendation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_offering_id')->constrained()->cascadeOnDelete();
            $table->string('match_type', 16); // same_family | related_career
            $table->string('via_kbji_code', 10)->nullable();
            $table->timestamps();
            $table->foreign('test_session_id')->references('id')->on('test_sessions')->cascadeOnDelete();
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 12); // program | career | campus
            $table->string('target_id', 64);
            $table->timestamps();
            $table->unique(['user_id', 'kind', 'target_id']);
        });

        Schema::create('development_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('target_key', 64);
            $table->string('custom_title', 160)->nullable();
            $table->string('category', 16)->nullable(); // akademik | softskill | karier
            $table->boolean('done')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'target_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('development_checks');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('umb_promotions');
        Schema::dropIfExists('recommendation_campuses');
        Schema::dropIfExists('recommendations');
    }
};
