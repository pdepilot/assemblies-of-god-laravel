<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterDraft extends Model
{
    protected $table = 'newsletter_drafts';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sections_json' => 'array',
        ];
    }
}
