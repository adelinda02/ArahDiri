<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accreditations', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 20); // campus | program_offering
            $table->unsignedBigInteger('subject_id');
            $table->string('rating_raw', 40);
            $table->unsignedTinyInteger('rating_score'); // 0-6, lihat App\Support\AccreditationRating
            $table->string('decree_no', 120)->nullable();
            $table->unsignedSmallInteger('decree_year')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('body', 24)->nullable(); // BAN-PT | LAM-INFOKOM | ...
            $table->timestamp('fetched_at');
            $table->timestamps();
            $table->unique(['subject_type', 'subject_id']);
        });

        Schema::create('accreditation_unmatched', function (Blueprint $table) {
            $table->id();
            $table->string('source', 12); // prodi | institusi
            $table->string('campus_name');
            $table->string('program_name')->nullable();
            $table->string('level', 16)->nullable();
            $table->json('raw');
            $table->timestamp('fetched_at');
            $table->boolean('resolved')->default(false);
            $table->timestamps();
        });

        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 24); // banpt | catalog
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('rows_in')->default(0);
            $table->unsignedInteger('rows_matched')->default(0);
            $table->string('status', 16)->default('running'); // running | ok | failed
            $table->longText('log')->nullable();
            $table->timestamps();
        });

        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 24);
            $table->string('slug', 80);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('status', 12)->default('active');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->unique(['kind', 'slug']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('subject_type', 40)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'content_items', 'sync_runs', 'accreditation_unmatched', 'accreditations'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
