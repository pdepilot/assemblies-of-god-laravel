<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SundaySchoolAward extends Model
{
    public $timestamps = false;

    protected $table = 'sunday_school_awards';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'recommended_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }
}
