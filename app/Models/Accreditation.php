<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Accreditation extends Model
{
    protected $fillable = ['subject_type', 'subject_id', 'rating_raw', 'rating_score', 'decree_no', 'decree_year', 'expires_at', 'body', 'fetched_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'date', 'fetched_at' => 'datetime'];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
