<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationCampus extends Model
{
    protected $fillable = ['recommendation_id', 'campus_id', 'program_offering_id', 'program_accreditation', 'campus_accreditation', 'accreditation_fetched_at', 'status'];

    protected function casts(): array
    {
        return ['program_accreditation' => 'array', 'campus_accreditation' => 'array', 'accreditation_fetched_at' => 'datetime'];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(ProgramOffering::class, 'program_offering_id');
    }
}
