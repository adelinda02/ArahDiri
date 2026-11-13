<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevelopmentCheck extends Model
{
    protected $fillable = ['user_id', 'target_key', 'custom_title', 'category', 'done'];

    protected function casts(): array
    {
        return ['done' => 'boolean'];
    }
}
