<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinistryRosterPerson extends Model
{
    protected $table = 'ministry_roster_people';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joined_date' => 'date',
        ];
    }

    public function linkedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'linked_member_id');
    }

    public function requiresParentDetails(): bool
    {
        return in_array((string) $this->ministry_key, ['children', 'teens'], true);
    }
}
