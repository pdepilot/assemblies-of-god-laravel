<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;

final class SdtgCommunityReadService
{
    /** @return list<array<string, mixed>> */
    public function listTestimonials(string $status = ''): array
    {
        $builder = DB::table('sdtg_testimonials')->orderByDesc('created_at');
        if ($status !== '') {
            $builder->where('status', $status);
        }

        return $builder->get()->map(fn ($row) => (array) $row)->all();
    }

    /** @return list<array<string, mixed>> */
    public function listPrayerRequests(string $status = ''): array
    {
        $builder = DB::table('sdtg_prayer_requests')->orderByDesc('created_at');
        if ($status !== '') {
            $builder->where('status', $status);
        }

        return $builder->get()->map(fn ($row) => (array) $row)->all();
    }

    /** @return list<array<string, mixed>> */
    public function listMemorySubmissions(string $status = ''): array
    {
        $builder = DB::table('sdtg_memory_submissions')->orderByDesc('created_at');
        if ($status !== '') {
            $builder->where('status', $status);
        }

        return $builder->get()->map(fn ($row) => (array) $row)->all();
    }
}
