<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $fillable = ['user_id', 'school_name', 'umb_program_offering_id', 'umb_semester', 'umb_self_declared', 'avatar', 'bio'];

    protected function casts(): array
    {
        return ['umb_self_declared' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function umbProgram(): BelongsTo
    {
        return $this->belongsTo(ProgramOffering::class, 'umb_program_offering_id');
    }
}
