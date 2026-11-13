<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/** Satu prodi di satu kampus (data PDDikti). */
class ProgramOffering extends Model
{
    protected $fillable = ['campus_id', 'program_family_id', 'program_code', 'original_name', 'level', 'field', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(ProgramFamily::class, 'program_family_id');
    }

    public function accreditation(): MorphOne
    {
        return $this->morphOne(Accreditation::class, 'subject');
    }
}
