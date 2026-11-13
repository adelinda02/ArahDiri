<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmbPromotion extends Model
{
    protected $fillable = ['test_session_id', 'recommendation_id', 'program_offering_id', 'match_type', 'via_kbji_code'];

    public function offering(): BelongsTo
    {
        return $this->belongsTo(ProgramOffering::class, 'program_offering_id');
    }
}
