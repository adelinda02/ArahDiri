<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResult extends Model
{
    protected $fillable = ['test_session_id', 'score_r', 'score_i', 'score_a', 'score_s', 'score_e', 'score_c', 'norm_r', 'norm_i', 'norm_a', 'norm_s', 'norm_e', 'norm_c', 'holland_code', 'scoring_version'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TestSession::class, 'test_session_id');
    }
}
