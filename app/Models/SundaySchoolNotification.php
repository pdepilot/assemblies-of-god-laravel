<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SundaySchoolNotification extends Model
{
    public $timestamps = false;

    protected $table = 'sunday_school_notifications';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
