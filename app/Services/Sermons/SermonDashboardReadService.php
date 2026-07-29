<?php

namespace App\Services\Sermons;

use Illuminate\Support\Facades\DB;

final class SermonDashboardReadService
{
    /** @return array<string, int> */
    public function getStats(): array
    {
        return [
            'sermons' => (int) DB::table('sermons')->count(),
            'published' => (int) DB::table('sermons')->where('status', 'published')->count(),
            'drafts' => (int) DB::table('sermons')->where('status', 'draft')->count(),
            'live_streams' => (int) DB::table('live_streams')->count(),
            'active_streams' => (int) DB::table('live_streams')->where('is_active', true)->count(),
            'categories' => (int) DB::table('sermon_categories')->where('is_active', true)->count(),
            'media_files' => (int) DB::table('sermon_media_library')->count(),
        ];
    }
}
