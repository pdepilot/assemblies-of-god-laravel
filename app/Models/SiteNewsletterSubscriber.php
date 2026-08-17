<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteNewsletterSubscriber extends Model
{
    protected $table = 'site_newsletter_subscribers';

    public const UPDATED_AT = 'updated_at';

    public const CREATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'ack_sent_at' => 'datetime',
            'location_updated_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
