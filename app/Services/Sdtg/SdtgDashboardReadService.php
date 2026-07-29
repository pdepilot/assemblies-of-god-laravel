<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;

final class SdtgDashboardReadService
{
    /** @return array<string, int> */
    public function getStats(): array
    {
        return [
            'registrations' => (int) DB::table('sdtg_registrations')->where('status', '<>', 'cancelled')->count(),
            'volunteers' => (int) DB::table('sdtg_registrations')->where('is_volunteer', true)->count(),
            'pending_volunteers' => (int) DB::table('sdtg_registrations')
                ->where('is_volunteer', true)
                ->where('volunteer_status', 'pending')
                ->count(),
            'speakers' => (int) DB::table('sdtg_speakers')->where('is_published', true)->count(),
            'announcements' => (int) DB::table('sdtg_announcements')->where('is_published', true)->count(),
            'testimonials_pending' => (int) DB::table('sdtg_testimonials')->where('status', 'pending')->count(),
            'prayer_new' => (int) DB::table('sdtg_prayer_requests')->where('status', 'new')->count(),
        ];
    }
}
