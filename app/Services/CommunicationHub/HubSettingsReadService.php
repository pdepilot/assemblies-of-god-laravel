<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;

final class HubSettingsReadService
{
    /** @return array<string, mixed> */
    public function getSettings(): array
    {
        $hub = DB::table('communication_settings')->where('id', 1)->first();
        if (! $hub) {
            DB::table('communication_settings')->insertOrIgnore(['id' => 1]);
            $hub = DB::table('communication_settings')->where('id', 1)->first();
        }

        $email = DB::table('email_settings')->where('id', 1)->first();
        if (! $email) {
            DB::table('email_settings')->insertOrIgnore([
                'id' => 1,
                'provider' => 'smtp',
                'from_email' => 'noreply@agikenebgu.com',
                'from_name' => 'Assemblies of God Ikenegbu',
            ]);
            $email = DB::table('email_settings')->where('id', 1)->first();
        }

        return [
            'hub' => $hub ? (array) $hub : [],
            'email' => $email ? (array) $email : [],
            'channels' => DB::table('communication_channels')->orderBy('sort_order')->get()->map(fn ($r) => (array) $r)->all(),
        ];
    }
}
