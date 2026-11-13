<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramFamilyJob extends Model
{
    public $timestamps = false;

    protected $fillable = ['program_family_id', 'kbji_code', 'rank'];

    public function family(): BelongsTo
    {
        return $this->belongsTo(ProgramFamily::class, 'program_family_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(KbjiJob::class, 'kbji_code', 'code');
    }
}
