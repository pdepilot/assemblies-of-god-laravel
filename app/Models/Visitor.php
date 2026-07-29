<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visitor extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'first_visit_date' => 'date',
            'last_visit_date' => 'date',
            'promoted_at' => 'datetime',
        ];
    }

    public function promotedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'promoted_member_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
