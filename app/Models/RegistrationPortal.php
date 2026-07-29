<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationPortal extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_opens' => 'datetime',
            'registration_closes' => 'datetime',
            'landing_config' => 'array',
            'registration_settings' => 'array',
        ];
    }

    public function registrants(): HasMany
    {
        return $this->hasMany(Registrant::class, 'portal_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(RegistrationField::class, 'portal_id');
    }
}
