<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SundaySchoolCertificate extends Model
{
    public $timestamps = false;

    protected $table = 'sunday_school_certificates';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(SundaySchoolAward::class, 'award_id');
    }
}
