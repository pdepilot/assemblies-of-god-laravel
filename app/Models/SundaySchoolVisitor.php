<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SundaySchoolVisitor extends Model
{
    public $timestamps = false;

    protected $table = 'sunday_school_visitors';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SundaySchoolClass::class, 'class_id');
    }
}
