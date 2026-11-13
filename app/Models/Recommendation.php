<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Recommendation extends Model
{
    protected $fillable = ['test_session_id', 'kind', 'rank', 'target_type', 'target_id', 'score', 'reason', 'engine_version'];

    protected function casts(): array
    {
        return ['reason' => 'array', 'score' => 'decimal:4'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TestSession::class, 'test_session_id');
    }

    public function campusPick(): HasOne
    {
        return $this->hasOne(RecommendationCampus::class);
    }
}
