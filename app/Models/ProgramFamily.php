<?php

namespace App\Models;

use App\Support\Riasec;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kelompok prodi senama per jenjang (mis. "teknik informatika|S1"), pembawa profil RIASEC. */
class ProgramFamily extends Model
{
    protected $fillable = ['key', 'name', 'level', 'field', 'is_teacher_education', 'riasec_r', 'riasec_i', 'riasec_a', 'riasec_s', 'riasec_e', 'riasec_c', 'holland_code', 'mapping_method', 'is_recommendable'];

    protected function casts(): array
    {
        return ['is_teacher_education' => 'boolean', 'is_recommendable' => 'boolean'] + Riasec::decimalCasts();
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(ProgramOffering::class);
    }

    public function jobLinks(): HasMany
    {
        return $this->hasMany(ProgramFamilyJob::class)->orderBy('rank');
    }
}
