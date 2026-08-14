<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\DB;

final class WebsiteMediaIndexReadService
{
    /** @return array<string, int> */
    public function getCounts(): array
    {
        $sermonMedia = (int) DB::table('sermon_media_library')->count();

        return [
            'sermon_media' => $sermonMedia,
            'total' => $sermonMedia,
        ];
    }
}
