<?php

namespace App\Models;

use App\Support\Riasec;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Program keahlian SMK (Kepmendikbudristek 244/M/2024). */
class SmkProgram extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'field_code', 'field_name', 'riasec_r', 'riasec_i', 'riasec_a', 'riasec_s', 'riasec_e', 'riasec_c', 'holland_code', 'mapping_status'];

    protected function casts(): array
    {
        return Riasec::decimalCasts();
    }

    public function concentrations(): HasMany
    {
        return $this->hasMany(SmkConcentration::class, 'smk_program_code', 'code');
    }
}
