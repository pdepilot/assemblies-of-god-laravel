<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinistryAgeTransfer extends Model
{
    protected $table = 'ministry_age_transfers';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'transferred_at' => 'datetime',
            'age_at_transfer' => 'integer',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function rosterPerson(): BelongsTo
    {
        return $this->belongsTo(MinistryRosterPerson::class, 'roster_person_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
