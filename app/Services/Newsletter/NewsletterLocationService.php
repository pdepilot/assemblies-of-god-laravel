<?php

namespace App\Services\Newsletter;

use App\Services\Security\IpGeoLookupService;
use App\Support\NigeriaStates;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class NewsletterLocationService
{
    public const SOURCE_USER = 'user';

    public const SOURCE_BROWSER = 'browser';

    public const SOURCE_IP = 'ip';

    public const ACCURACY_CONFIRMED = 'confirmed';

    public const ACCURACY_PRECISE = 'precise';

    public const ACCURACY_ESTIMATED = 'estimated';

    public function __construct(
        private readonly IpGeoLookupService $ipGeo,
    ) {}

    /**
     * Priority: user-confirmed > browser (with permission) > IP estimate.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function resolveFromRequest(array $input, ?string $ipAddress): array
    {
        $user = $this->fromUserInput($input);
        if ($user !== null) {
            return $user;
        }

        $browser = $this->fromBrowserInput($input);
        if ($browser !== null) {
            return $browser;
        }

        return $this->fromIp($ipAddress);
    }

    public function shouldReplace(?string $existingSource, ?string $newSource): bool
    {
        if ($newSource === null || $newSource === '') {
            return false;
        }

        if ($existingSource === null || $existingSource === '') {
            return true;
        }

        return $this->sourcePriority($newSource) >= $this->sourcePriority($existingSource);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{location: ?string, source_label: ?string, accuracy_label: ?string}
     */
    public function presentation(array $row): array
    {
        $parts = array_values(array_filter([
            trim((string) ($row['city'] ?? '')),
            trim((string) ($row['region'] ?? '')),
            trim((string) ($row['country'] ?? '')),
        ], static fn (string $part): bool => $part !== ''));

        $location = $parts !== [] ? implode(', ', $parts) : null;
        $source = trim((string) ($row['location_source'] ?? ''));
        $accuracy = trim((string) ($row['location_accuracy'] ?? ''));

        if ($location === null && trim((string) ($row['ip_address'] ?? '')) !== '') {
            return [
                'location' => 'Not resolved yet',
                'source_label' => 'IP address stored — location pending',
                'accuracy_label' => 'Estimated (not resolved)',
            ];
        }

        if ($location === null) {
            return [
                'location' => null,
                'source_label' => null,
                'accuracy_label' => null,
            ];
        }

        return [
            'location' => $location,
            'source_label' => match ($source) {
                self::SOURCE_USER => 'Subscriber confirmed',
                self::SOURCE_BROWSER => 'Browser location',
                self::SOURCE_IP => 'IP estimate',
                default => null,
            },
            'accuracy_label' => match ($accuracy) {
                self::ACCURACY_CONFIRMED => 'Confirmed',
                self::ACCURACY_PRECISE => 'Precise (device)',
                self::ACCURACY_ESTIMATED => 'Estimated',
                default => null,
            },
        ];
    }

    /** @param  array<string, mixed>  $input */
    private function fromUserInput(array $input): ?array
    {
        $country = $this->clean((string) ($input['country'] ?? ''));
        $region = NigeriaStates::normalize((string) ($input['region'] ?? ''))
            ?? $this->clean((string) ($input['region'] ?? ''));
        $city = $this->clean((string) ($input['city'] ?? ''));

        if ($country === null && $region === null && $city === null) {
            return null;
        }

        return $this->payload(
            country: $country,
            region: $region,
            city: $city,
            latitude: null,
            longitude: null,
            source: self::SOURCE_USER,
            accuracy: self::ACCURACY_CONFIRMED,
        );
    }

    /** @param  array<string, mixed>  $input */
    private function fromBrowserInput(array $input): ?array
    {
        if (! filter_var($input['browser_location_consent'] ?? false, FILTER_VALIDATE_BOOL)) {
            return null;
        }

        $lat = $this->latitude($input['latitude'] ?? null);
        $lng = $this->longitude($input['longitude'] ?? null);
        if ($lat === null || $lng === null) {
            return null;
        }

        $reverse = $this->reverseGeocode($lat, $lng);

        return $this->payload(
            country: $reverse['country'] ?? null,
            region: $reverse['region'] ?? null,
            city: $reverse['city'] ?? null,
            latitude: $lat,
            longitude: $lng,
            source: self::SOURCE_BROWSER,
            accuracy: self::ACCURACY_PRECISE,
        );
    }

    /** @return array<string, mixed> */
    private function fromIp(?string $ipAddress): array
    {
        $ip = trim((string) $ipAddress);
        if ($ip === '') {
            return [];
        }

        try {
            $geo = $this->ipGeo->lookup($ip);
        } catch (\Throwable $e) {
            Log::warning('newsletter.ip_geo_failed', ['message' => $e->getMessage()]);

            return [];
        }

        if (($geo['country'] ?? null) === null && ($geo['region'] ?? null) === null && ($geo['city'] ?? null) === null) {
            return [];
        }

        return $this->payload(
            country: $geo['country'] ?? null,
            region: isset($geo['region']) ? (NigeriaStates::normalize((string) $geo['region']) ?? $geo['region']) : null,
            city: $geo['city'] ?? null,
            latitude: isset($geo['latitude']) ? (float) $geo['latitude'] : null,
            longitude: isset($geo['longitude']) ? (float) $geo['longitude'] : null,
            source: self::SOURCE_IP,
            accuracy: self::ACCURACY_ESTIMATED,
        );
    }

    /** @return array{country: ?string, region: ?string, city: ?string} */
    private function reverseGeocode(float $latitude, float $longitude): array
    {
        if (! (bool) config('services.geocoding.enabled', true)) {
            return [];
        }

        $timeout = max(1, min(5, (int) config('services.geocoding.timeout_seconds', 3)));

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => (string) config('services.geocoding.user_agent', 'AGC-Ikenegbu-Newsletter/1.0'),
                    'Accept' => 'application/json',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'addressdetails' => 1,
                    'zoom' => 10,
                ]);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();
            if (! is_array($data)) {
                return [];
            }

            $address = is_array($data['address'] ?? null) ? $data['address'] : [];
            $country = $this->clean((string) ($address['country'] ?? ''));
            $region = NigeriaStates::normalize((string) ($address['state'] ?? $address['region'] ?? ''))
                ?? $this->clean((string) ($address['state'] ?? $address['region'] ?? ''));
            $city = $this->clean((string) ($address['city'] ?? $address['town'] ?? $address['village'] ?? $address['county'] ?? ''));

            return [
                'country' => $country,
                'region' => $region,
                'city' => $city,
            ];
        } catch (\Throwable $e) {
            Log::warning('newsletter.reverse_geocode_failed', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /** @return array<string, mixed> */
    private function payload(
        ?string $country,
        ?string $region,
        ?string $city,
        ?float $latitude,
        ?float $longitude,
        string $source,
        string $accuracy,
    ): array {
        return array_filter([
            'country' => $country,
            'region' => $region,
            'city' => $city,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location_source' => $source,
            'location_accuracy' => $accuracy,
            'location_updated_at' => now(),
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function sourcePriority(string $source): int
    {
        return match ($source) {
            self::SOURCE_USER => 3,
            self::SOURCE_BROWSER => 2,
            self::SOURCE_IP => 1,
            default => 0,
        };
    }

    private function clean(string $value): ?string
    {
        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function coordinate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 7);
    }

    private function latitude(mixed $value): ?float
    {
        $float = $this->coordinate($value);
        if ($float === null || $float < -90 || $float > 90) {
            return null;
        }

        return $float;
    }

    private function longitude(mixed $value): ?float
    {
        $float = $this->coordinate($value);
        if ($float === null || $float < -180 || $float > 180) {
            return null;
        }

        return $float;
    }
}
