<?php

namespace App\Services\Newsletter;

use App\Models\SiteNewsletterSubscriber;
use App\Services\Security\IpGeoLookupService;
use App\Support\NigeriaStates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

final class SubscriberLocationService
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

    public function columnsExist(): bool
    {
        return Schema::hasColumn('site_newsletter_subscribers', 'location_source');
    }

    /**
     * Capture location from the subscribe request without calling external APIs.
     *
     * @return array<string, mixed>
     */
    public function captureFromRequest(Request $request): array
    {
        if (! $this->columnsExist()) {
            return [];
        }

        $country = $this->clean($request->input('country'));
        $region = $this->clean($request->input('region'));
        $city = $this->clean($request->input('city'));
        $lat = $this->optionalFloat($request->input('latitude'), -90, 90);
        $lng = $this->optionalFloat($request->input('longitude'), -180, 180);
        if (($lat === null) !== ($lng === null)) {
            $lat = null;
            $lng = null;
        }

        [$country, $region] = NigeriaStates::normalize($country, $region);

        if ($country !== null || $region !== null) {
            return [
                'country' => $country,
                'region' => $region,
                'city' => $city,
                'latitude' => $lat,
                'longitude' => $lng,
                'location_source' => self::SOURCE_USER,
                'location_accuracy' => self::ACCURACY_CONFIRMED,
                'location_updated_at' => now(),
            ];
        }

        if ($lat !== null && $lng !== null) {
            return [
                'latitude' => $lat,
                'longitude' => $lng,
                'location_source' => self::SOURCE_BROWSER,
                'location_accuracy' => self::ACCURACY_PRECISE,
                'location_updated_at' => now(),
            ];
        }

        return [
            'location_source' => self::SOURCE_IP,
            'location_accuracy' => self::ACCURACY_ESTIMATED,
        ];
    }

    public function needsEnrichment(array $captured): bool
    {
        $source = (string) ($captured['location_source'] ?? '');

        return $source === self::SOURCE_BROWSER || $source === self::SOURCE_IP;
    }

    public function enrich(int $subscriberId): void
    {
        try {
            if (! $this->columnsExist()) {
                return;
            }

            $row = SiteNewsletterSubscriber::query()->find($subscriberId);
            if ($row === null) {
                return;
            }

            $source = (string) ($row->location_source ?? '');
            if ($source === self::SOURCE_USER) {
                return;
            }

            if ($source === self::SOURCE_BROWSER && $row->latitude !== null && $row->longitude !== null) {
                $geo = $this->reverseGeocode((float) $row->latitude, (float) $row->longitude);
                if ($geo !== []) {
                    $row->fill($geo + ['location_updated_at' => now()])->save();
                }

                return;
            }

            $ip = trim((string) ($row->ip_address ?? ''));
            if ($ip === '') {
                return;
            }

            $geo = $this->ipGeo->lookup($ip);
            [$country, $region] = NigeriaStates::normalize(
                $geo['country'] ?? null,
                $geo['region'] ?? null,
            );

            $row->fill([
                'country' => $country,
                'region' => $region,
                'city' => $this->clean($geo['city'] ?? null),
                'location_source' => self::SOURCE_IP,
                'location_accuracy' => self::ACCURACY_ESTIMATED,
                'location_updated_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('Newsletter location enrich failed', [
                'subscriber_id' => $subscriberId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{label: string, source_label: string, accuracy_label: string, is_estimate: bool}
     */
    public function present(array $row): array
    {
        $parts = array_values(array_filter([
            trim((string) ($row['city'] ?? '')),
            trim((string) ($row['region'] ?? '')),
            trim((string) ($row['country'] ?? '')),
        ], fn (string $part): bool => $part !== ''));

        $source = (string) ($row['location_source'] ?? '');
        $accuracy = (string) ($row['location_accuracy'] ?? '');
        $label = $parts !== [] ? implode(', ', $parts) : '';

        if ($label === '' && $source === self::SOURCE_IP) {
            $label = 'Not resolved';
        }

        return [
            'label' => $label !== '' ? $label : '—',
            'source_label' => match ($source) {
                self::SOURCE_USER => 'Subscriber confirmed',
                self::SOURCE_BROWSER => 'Browser location',
                self::SOURCE_IP => 'IP estimate',
                default => 'Unknown',
            },
            'accuracy_label' => match ($accuracy) {
                self::ACCURACY_CONFIRMED => 'Confirmed',
                self::ACCURACY_PRECISE => 'Precise (device)',
                self::ACCURACY_ESTIMATED => 'Estimated',
                default => $label === '' ? 'Not resolved' : 'Unknown',
            },
            'is_estimate' => $source === self::SOURCE_IP || $accuracy === self::ACCURACY_ESTIMATED,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reverseGeocode(float $lat, float $lng): array
    {
        $cacheKey = 'nl-revgeo:'.round($lat, 3).':'.round($lng, 3);

        /** @var array<string, mixed> $result */
        $result = Cache::remember($cacheKey, now()->addDays(14), function () use ($lat, $lng): array {
            try {
                $contact = (string) config('identity.email.from_email', 'info@agikenebgu.org');
                $response = Http::timeout(4)
                    ->acceptJson()
                    ->withHeaders([
                        'User-Agent' => 'AGCIkenegbuChurch/1.0 ('.$contact.')',
                    ])
                    ->get('https://nominatim.openstreetmap.org/reverse', [
                        'lat' => $lat,
                        'lon' => $lng,
                        'format' => 'jsonv2',
                        'addressdetails' => 1,
                        'zoom' => 10,
                    ]);

                if (! $response->successful()) {
                    return [];
                }

                $data = $response->json();
                $address = is_array($data['address'] ?? null) ? $data['address'] : [];
                $country = $this->clean($address['country'] ?? null);
                $region = $this->clean($address['state'] ?? ($address['region'] ?? null));
                $city = $this->clean($address['city'] ?? ($address['town'] ?? ($address['village'] ?? null)));
                [$country, $region] = NigeriaStates::normalize($country, $region);

                return array_filter([
                    'country' => $country,
                    'region' => $region,
                    'city' => $city,
                    'location_source' => self::SOURCE_BROWSER,
                    'location_accuracy' => self::ACCURACY_PRECISE,
                ], fn ($value) => $value !== null && $value !== '');
            } catch (\Throwable $e) {
                Log::notice('Newsletter reverse geocode failed', ['error' => $e->getMessage()]);

                return [];
            }
        });

        return is_array($result) ? $result : [];
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? mb_substr($value, 0, 80) : null;
    }

    private function optionalFloat(mixed $value, float $min, float $max): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }
        $number = (float) $value;
        if ($number < $min || $number > $max) {
            return null;
        }

        return $number;
    }
}
