<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchEvent extends Model
{
    use HasFactory;

    protected $table = 'church_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_recurring' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
