<?php

namespace App\Models;

use App\Support\Riasec;
use Illuminate\Database\Eloquent\Model;

/** Jabatan KBJI 2026 (katalog karier utama). */
class KbjiJob extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'title', 'official_title', 'subgroup_code', 'subgroup_title', 'major_group', 'description', 'print_page', 'pdf_page', 'riasec_r', 'riasec_i', 'riasec_a', 'riasec_s', 'riasec_e', 'riasec_c', 'holland_code', 'mapping_status', 'job_zone_min', 'job_zone_max', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'] + Riasec::decimalCasts();
    }
}
