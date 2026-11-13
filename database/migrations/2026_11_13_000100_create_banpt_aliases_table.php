<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pasangan manual nama kampus BAN-PT → kampus katalog (diisi admin dari "akreditasi tidak cocok"). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banpt_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('banpt_name');
            $table->string('name_key', 191)->unique();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banpt_aliases');
    }
};
