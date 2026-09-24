<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsitePromotion extends Model
{
    protected $table = 'website_promotions';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_every_visit' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
