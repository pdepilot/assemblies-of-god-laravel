<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class IpGeoLookupService
{
    /**
     * @return array{country: ?string, region: ?string, city: ?string, isp: ?string, latitude: ?float, longitude: ?float, label: string}
     */
    public function lookup(string $ip): array
    {
        $ip = trim($ip);
        $empty = [
            'country' => null,
            'region' => null,
            'city' => null,
            'isp' => null,
            'latitude' => null,
            'longitude' => null,
            'label' => 'Unknown location',
        ];

        if ($ip === '' || $this->isPrivateIp($ip)) {
            return array_merge($empty, ['label' => 'Local / private network']);
        }

        return Cache::remember('ip-geo:'.$ip, now()->addDay(), function () use ($ip, $empty) {
            try {
                $response = Http::timeout(3)
                    ->acceptJson()
                    ->get('http://ip-api.com/json/'.$ip, [
                        'fields' => 'status,country,regionName,city,lat,lon,isp,query',
                    ]);

                if (! $response->successful()) {
                    return $empty;
                }

                $data = $response->json();
                if (! is_array($data) || ($data['status'] ?? '') !== 'success') {
                    return $empty;
                }

                $city = trim((string) ($data['city'] ?? ''));
                $region = trim((string) ($data['regionName'] ?? ''));
                $country = trim((string) ($data['country'] ?? ''));
                $isp = trim((string) ($data['isp'] ?? ''));
                $latitude = is_numeric($data['lat'] ?? null) ? (float) $data['lat'] : null;
                $longitude = is_numeric($data['lon'] ?? null) ? (float) $data['lon'] : null;
                $parts = array_values(array_filter([$city, $region, $country]));

                return [
                    'country' => $country !== '' ? $country : null,
                    'region' => $region !== '' ? $region : null,
                    'city' => $city !== '' ? $city : null,
                    'isp' => $isp !== '' ? $isp : null,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'label' => $parts !== [] ? implode(', ', $parts) : 'Unknown location',
                ];
            } catch (\Throwable) {
                return $empty;
            }
        });
    }

    private function isPrivateIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
