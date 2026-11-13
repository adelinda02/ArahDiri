<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmkConcentration extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['code', 'smk_program_code', 'name', 'duration_years'];
}
