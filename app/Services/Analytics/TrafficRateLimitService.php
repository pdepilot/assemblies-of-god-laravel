<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;

final class TrafficRateLimitService
{
    public function allow(string $ip): bool
    {
        try {
            $max = (int) config('traffic.rate_limit_max', 120);
            $window = (int) config('traffic.rate_limit_window', 300);
            $key = hash('sha256', 'site_traffic:'.$ip);
            $now = now();

            $row = DB::table('rate_limits')->where('rate_key', $key)->first();

            if (! $row) {
                DB::table('rate_limits')->insert([
                    'rate_key' => $key,
                    'hits' => 1,
                    'expires_at' => $now->copy()->addSeconds($window),
                ]);

                return true;
            }

            if ($now->greaterThanOrEqualTo($row->expires_at)) {
                DB::table('rate_limits')->where('rate_key', $key)->update([
                    'hits' => 1,
                    'expires_at' => $now->copy()->addSeconds($window),
                ]);

                return true;
            }

            if ((int) $row->hits >= $max) {
                return false;
            }

            DB::table('rate_limits')->where('rate_key', $key)->increment('hits');

            return true;
        } catch (\Throwable) {
            return true;
        }
    }
}
