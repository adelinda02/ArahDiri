<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Kolom profil RIASEC skala O*NET 1-7. */
    private function riasec(Blueprint $table): void
    {
        foreach (['r', 'i', 'a', 's', 'e', 'c'] as $d) {
            $table->decimal('riasec_'.$d, 4, 2)->nullable();
        }
        $table->string('holland_code', 3)->nullable()->index();
    }

    public function up(): void
    {
        Schema::create('kbji_jobs', function (Blueprint $table) {
            $table->string('code', 10)->primary(); // mis. 2512.03
            $table->string('title');
            $table->string('official_title');
            $table->string('subgroup_code', 4)->index();
            $table->string('subgroup_title')->nullable();
            $table->char('major_group', 1)->index();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('print_page')->nullable();
            $table->unsignedSmallInteger('pdf_page')->nullable();
            $this->riasec($table);
            $table->string('mapping_status', 24)->default('unmapped'); // document_supported | draft_ai | unmapped
            $table->unsignedTinyInteger('job_zone_min')->nullable();
            $table->unsignedTinyInteger('job_zone_max')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kbji_onet_links', function (Blueprint $table) {
            $table->id();
            $table->string('kbji_code', 10)->index();
            $table->string('onet_code', 10);
            $table->string('relation', 12); // exact | broader | narrower | related
            $table->string('status', 24);
            $table->unique(['kbji_code', 'onet_code']);
        });

        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('pt_code', 6)->unique();
            $table->string('name');
            $table->string('group', 40)->nullable(); // PTN, PTS, PTA (Keagamaan), PTK (Kedinasan)
            $table->string('ownership', 40)->nullable(); // Negeri | Negeri (Kedinasan) | PTA (Keagamaan) | Swasta
            $table->string('form', 40)->nullable(); // Universitas, Institut, ...
            $table->string('supervisor', 120)->nullable();
            $table->string('province', 100)->nullable()->index();
            $table->string('city', 100)->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_state')->default(false)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('name');
        });

        Schema::create('program_families', function (Blueprint $table) {
            $table->id();
            $table->string('key', 160)->unique(); // nama_prodi_normal|jenjang
            $table->string('name');
            $table->string('level', 16)->index();
            $table->string('field', 40)->nullable()->index();
            $table->boolean('is_teacher_education')->default(false);
            $this->riasec($table);
            $table->string('mapping_method', 24)->nullable();
            $table->boolean('is_recommendable')->default(true);
            $table->timestamps();
        });

        Schema::create('program_family_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_family_id')->constrained()->cascadeOnDelete();
            $table->string('kbji_code', 10)->index();
            $table->unsignedTinyInteger('rank');
            $table->unique(['program_family_id', 'kbji_code']);
        });

        Schema::create('program_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_family_id')->nullable()->constrained()->nullOnDelete();
            $table->string('program_code', 10);
            $table->string('original_name');
            $table->string('level', 16)->index();
            $table->string('field', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['campus_id', 'program_code', 'level']);
        });

        Schema::create('smk_programs', function (Blueprint $table) {
            $table->string('code', 8)->primary(); // mis. 2.2
            $table->string('name');
            $table->string('field_code', 4);
            $table->string('field_name');
            $this->riasec($table);
            $table->string('mapping_status', 24)->default('draft_ai');
            $table->timestamps();
        });

        Schema::create('smk_concentrations', function (Blueprint $table) {
            $table->string('code', 10)->primary(); // mis. 2.2.1
            $table->string('smk_program_code', 8)->index();
            $table->string('name');
            $table->unsignedTinyInteger('duration_years')->default(3);
        });

        Schema::create('smk_program_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('smk_program_code', 8)->index();
            $table->string('kbji_code', 10);
            $table->unsignedTinyInteger('rank');
            $table->unique(['smk_program_code', 'kbji_code']);
        });

        Schema::create('sma_tracks', function (Blueprint $table) {
            $table->string('code', 10)->primary(); // ipa | ips | bahasa
            $table->string('name');
            $table->json('subjects');
            $this->riasec($table);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['sma_tracks', 'smk_program_jobs', 'smk_concentrations', 'smk_programs', 'program_offerings', 'program_family_jobs', 'program_families', 'campuses', 'kbji_onet_links', 'kbji_jobs'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
