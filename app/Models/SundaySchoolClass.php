<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SundaySchoolClass extends Model
{
    protected $table = 'sunday_school_classes';

    protected $guarded = [];

    protected $casts = [];

    public function students(): HasMany
    {
        return $this->hasMany(SundaySchoolStudent::class, 'class_id');
    }
}

