<?php

namespace App\Services\Settings;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PlatformSettingsReadService
{
    /** @var array<string, array<string, mixed>> */
    private array $groupMemo = [];

    private ?bool $groupsTableExists = null;

    /** @var array<string, array<string, mixed>> */
    private array $storedMemo = [];

    /** @return array<string, mixed> */
    public function getGroup(string $groupKey): array
    {
        if (isset($this->groupMemo[$groupKey])) {
            return $this->groupMemo[$groupKey];
        }

        $defaults = $this->defaults()[$groupKey] ?? [];
        $stored = $this->readStored($groupKey);

        return $this->groupMemo[$groupKey] = array_replace_recursive($defaults, $stored);
    }

    /**
     * Overlay Admin → Settings → Church Profile onto the public chrome church array.
     * Non-empty platform fields win; empty fields keep the existing (contact_settings/default) value.
     *
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    public function overlayPublicChurch(array $profile): array
    {
        $church = $this->readStored('church');

        $name = trim((string) ($church['name'] ?? ''));
        if ($name !== '') {
            $profile['church_name'] = $name;
        }

        $short = trim((string) ($church['short_name'] ?? ''));
        if ($short !== '') {
            $profile['short_name'] = $short;
        }

        $email = trim((string) ($church['email'] ?? ''));
        if ($email !== '') {
            $profile['email'] = $email;
        }

        $phone = trim((string) ($church['phone'] ?? ''));
        if ($phone !== '') {
            $profile['phone_display'] = $phone;
            $tel = preg_replace('/[^\d+]/', '', $phone) ?: $phone;
            $profile['phone'] = $tel;
            $profile['phone_tel'] = $tel;
        }

        $street = trim((string) ($church['address'] ?? ''));
        if ($street !== '') {
            $addressParts = array_filter([
                $street,
                $church['city'] ?? '',
                $church['state'] ?? '',
            ], static fn ($part) => trim((string) $part) !== '');
            $profile['address_full'] = implode(', ', $addressParts);
        }

        $sunday = trim((string) ($church['service_sunday'] ?? ''));
        if ($sunday !== '') {
            $profile['sunday_worship'] = $sunday;
        }

        $midweek = trim((string) ($church['service_midweek'] ?? ''));
        if ($midweek !== '') {
            $profile['midweek_service'] = $midweek;
        }

        $social = is_array($profile['social'] ?? null) ? $profile['social'] : [];
        foreach ([
            'facebook' => 'social_facebook',
            'instagram' => 'social_instagram',
            'youtube' => 'social_youtube',
        ] as $publicKey => $platformKey) {
            $url = trim((string) ($church[$platformKey] ?? ''));
            if ($url !== '') {
                $social[$publicKey] = $url;
            }
        }
        $profile['social'] = $social;

        return $profile;
    }

    public function forgetMemo(): void
    {
        $this->groupMemo = [];
        $this->storedMemo = [];
    }

    /**
     * @return array{
     *   general: array<string, mixed>,
     *   church: array<string, mixed>,
     *   website_design: array<string, mixed>,
     *   rbac: array<string, mixed>
     * }
     */
    public function getEditableGroups(): array
    {
        return [
            'general' => $this->getGroup('general'),
            'church' => $this->enrichChurch($this->getGroup('church')),
            'website_design' => $this->getGroup('website_design'),
            'rbac' => $this->getGroup('rbac'),
        ];
    }

    /** Absolute filesystem path for the configured church logo, if readable. */
    public function churchLogoAbsolutePath(): ?string
    {
        $path = trim((string) ($this->getGroup('church')['logo_path'] ?? ''));
        if ($path === '') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($path, 'site/')) {
            $path = substr($path, 5);
        }

        $absolute = public_path('site/'.$path);
        if (is_readable($absolute)) {
            return $absolute;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $church
     * @return array<string, mixed>
     */
    private function enrichChurch(array $church): array
    {
        $path = trim((string) ($church['logo_path'] ?? ''));
        $church['logo_url'] = $path !== ''
            ? app(\App\Services\PublicSite\PublicAssetResolver::class)->url($path)
            : '';

        return $church;
    }

    /** @return array<string, int|string|bool> */
    public function overviewStats(): array
    {
        $rbac = $this->getGroup('rbac');

        return [
            'admins' => Schema::hasTable('admins') ? (int) DB::table('admins')->count() : 0,
            'members' => Schema::hasTable('members') ? (int) DB::table('members')->count() : 0,
            'roles' => Schema::hasTable('roles') ? (int) DB::table('roles')->count() : 0,
            'sessions' => Schema::hasTable('admin_sessions') ? (int) DB::table('admin_sessions')->count() : 0,
            'church_name' => (string) ($this->getGroup('church')['name'] ?? config('identity.public.site_name', 'AGC Ikenegbu Assemblies of God')),
            'short_name' => (string) ($this->getGroup('church')['short_name'] ?? config('identity.admin.brand_name', 'AGC IKENEGBU')),
            'rbac_enabled' => (bool) ($rbac['enforcement_enabled'] ?? false),
            'platform_name' => (string) config('app.name', 'AGC IKENEGBU Church ERP'),
        ];
    }

    /**
     * @return list<array{id: string, label: string, icon: string, super_only: bool}>
     */
    public function tabs(bool $isSuper): array
    {
        $tabs = [
            ['id' => 'overview', 'label' => 'Overview', 'icon' => 'fa-gauge-high', 'super_only' => false],
            ['id' => 'general', 'label' => 'General', 'icon' => 'fa-sliders', 'super_only' => false],
            ['id' => 'church', 'label' => 'Church Profile', 'icon' => 'fa-church', 'super_only' => false],
            ['id' => 'website_design', 'label' => 'Website Design', 'icon' => 'fa-palette', 'super_only' => false],
            ['id' => 'preferences', 'label' => 'My Preferences', 'icon' => 'fa-user-gear', 'super_only' => false],
            ['id' => 'admins', 'label' => 'Administrators', 'icon' => 'fa-user-shield', 'super_only' => true],
            ['id' => 'roles', 'label' => 'Roles & Permissions', 'icon' => 'fa-key', 'super_only' => true],
            ['id' => 'communication', 'label' => 'Communication', 'icon' => 'fa-envelope', 'super_only' => false],
            ['id' => 'related', 'label' => 'More Modules', 'icon' => 'fa-link', 'super_only' => false],
        ];

        return array_values(array_filter(
            $tabs,
            static fn (array $tab): bool => ! $tab['super_only'] || $isSuper
        ));
    }

    /** @return array<string, mixed> */
    private function readStored(string $groupKey): array
    {
        if (array_key_exists($groupKey, $this->storedMemo)) {
            return $this->storedMemo[$groupKey];
        }

        if (! ($this->groupsTableExists ??= Schema::hasTable('platform_setting_groups'))) {
            return $this->storedMemo[$groupKey] = [];
        }

        $raw = DB::table('platform_setting_groups')->where('group_key', $groupKey)->value('settings');
        if (! is_string($raw) || $raw === '') {
            return $this->storedMemo[$groupKey] = [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return $this->storedMemo[$groupKey] = [];
        }

        return $this->storedMemo[$groupKey] = is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, array<string, mixed>> */
    private function defaults(): array
    {
        return [
            'general' => [
                'timezone' => 'Africa/Lagos',
                'language' => 'en',
                'currency' => 'NGN',
                'date_format' => 'dmy',
                'records_per_page' => 25,
            ],
            'church' => [
                'name' => (string) config('identity.public.site_name', 'AGC Ikenegbu Assemblies of God'),
                'short_name' => (string) config('identity.admin.brand_name', 'AGC IKENEGBU'),
                'pastor' => '',
                'founded_year' => 1988,
                'address' => '',
                'city' => 'Owerri',
                'state' => 'Imo',
                'phone' => '',
                'email' => '',
                'website' => '',
                'service_sunday' => '',
                'service_midweek' => '',
                'logo_path' => '',
                'social_facebook' => '',
                'social_instagram' => '',
                'social_youtube' => '',
            ],
            'website_design' => [
                'primary_color' => '#FBFE06',
                'secondary_color' => '#8B95A8',
                'dark_color' => '#1A2B5C',
                'text_color' => '#1A2B5C',
                'background_color' => '#FFFFFF',
                'accent_color' => '#C9A227',
            ],
            'rbac' => [
                'enforcement_enabled' => false,
                'debug_enabled' => false,
            ],
        ];
    }
}
