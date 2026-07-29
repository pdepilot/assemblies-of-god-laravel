<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\DB;

final class WebsiteMediaIndexReadService
{
    /** @return array<string, int> */
    public function getCounts(): array
    {
        $sermonMedia = (int) DB::table('sermon_media_library')->count();
        $sdtgMedia = (int) DB::table('sdtg_media_assets')->count();

        return [
            'sermon_media' => $sermonMedia,
            'sdtg_media' => $sdtgMedia,
            'total' => $sermonMedia + $sdtgMedia,
        ];
    }
}
