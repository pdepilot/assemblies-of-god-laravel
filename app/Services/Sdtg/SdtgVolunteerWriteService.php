<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;

final class SdtgVolunteerWriteService
{
    public function approve(int $id): void
    {
        DB::table('sdtg_registrations')->where('id', $id)->update([
            'volunteer_status' => 'approved',
        ]);
    }

    public function decline(int $id): void
    {
        DB::table('sdtg_registrations')->where('id', $id)->update([
            'volunteer_status' => 'declined',
        ]);
    }
}
