<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SundaySchoolTeacher extends Model
{
    protected $table = 'sunday_school_teachers';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_joined' => 'date',
        ];
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SundaySchoolClass::class, 'class_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function classesAsTeacher(): HasMany
    {
        return $this->hasMany(SundaySchoolClass::class, 'teacher_id');
    }
}
