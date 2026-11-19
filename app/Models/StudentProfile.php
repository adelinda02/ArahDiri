<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $fillable = ['user_id', 'school_name', 'current_program_offering_id', 'current_semester', 'current_self_declared', 'feels_mismatch', 'avatar', 'bio'];

    protected function casts(): array
    {
        return ['current_self_declared' => 'boolean', 'feels_mismatch' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentProgram(): BelongsTo
    {
        return $this->belongsTo(ProgramOffering::class, 'current_program_offering_id');
    }
}
