<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteNewsletterSubscriber extends Model
{
    protected $table = 'site_newsletter_subscribers';

    public const UPDATED_AT = 'updated_at';

    public const CREATED_AT = null;

    protected $guarded = [];
}
