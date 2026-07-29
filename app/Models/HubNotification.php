<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HubNotification extends Model
{
    protected $table = 'notification_center';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
            'is_read' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }
}
