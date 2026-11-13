<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Campus extends Model
{
    protected $fillable = ['pt_code', 'name', 'group', 'ownership', 'form', 'supervisor', 'province', 'city', 'website', 'is_state', 'is_active'];

    protected function casts(): array
    {
        return ['is_state' => 'boolean', 'is_active' => 'boolean'];
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(ProgramOffering::class);
    }

    public function accreditation(): MorphOne
    {
        return $this->morphOne(Accreditation::class, 'subject');
    }

    public function scopeUmb(Builder $q): Builder
    {
        return $q->where('pt_code', config('arahdiri.umb_pt_code'));
    }
}
