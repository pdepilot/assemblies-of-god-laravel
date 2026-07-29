<?php

namespace App\Services\Ministries;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class MinistrySettingsReadService
{
    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return DB::table('ministry_settings')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => $this->formatSetting((array) $row))
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function enabled(): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (array $row): bool => (bool) ($row['is_enabled'] ?? false),
        ));
    }

    /** @return array<string, mixed>|null */
    public function getByKey(string $key): ?array
    {
        $row = DB::table('ministry_settings')->where('ministry_key', $key)->first();

        return $row ? $this->formatSetting((array) $row) : null;
    }

    public function classifyMember(?int $age, string $gender, string $maritalStatus = 'unspecified'): ?string
    {
        if ($age === null) {
            return null;
        }

        $gender = strtolower(trim($gender));
        $marital = strtolower(trim($maritalStatus));

        if ($age < 13) {
            return $this->pickAutoKey('children');
        }

        if ($age <= 19) {
            return $this->pickAutoKey('teens');
        }

        if ($marital === 'single') {
            return $this->pickAutoKey('youths');
        }

        if ($marital === 'widow' || ($marital === 'widowed' && $gender === 'female')) {
            return $this->pickAutoKey('widows');
        }

        if ($marital === 'widower' || ($marital === 'widowed' && $gender === 'male')) {
            return $this->pickAutoKey('widowers');
        }

        if ($marital === 'widowed') {
            return null;
        }

        if (in_array($marital, ['married', 'divorced', 'separated'], true)) {
            if ($gender === 'male') {
                return $this->pickAutoKey('men');
            }
            if ($gender === 'female') {
                return $this->pickAutoKey('women');
            }
        }

        return null;
    }

    public function memberAge(?string $dateOfBirth): ?int
    {
        if (! $dateOfBirth) {
            return null;
        }

        try {
            return Carbon::parse($dateOfBirth)->age;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatSetting(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'ministry_key' => (string) $row['ministry_key'],
            'name' => (string) $row['name'],
            'min_age' => $row['min_age'] !== null ? (int) $row['min_age'] : null,
            'max_age' => $row['max_age'] !== null ? (int) $row['max_age'] : null,
            'gender_filter' => (string) $row['gender_filter'],
            'assignment_mode' => (string) $row['assignment_mode'],
            'is_enabled' => (bool) $row['is_enabled'],
            'sort_order' => (int) $row['sort_order'],
        ];
    }

    private function pickAutoKey(string $key): ?string
    {
        $setting = $this->getByKey($key);
        if (! $setting || ! ($setting['is_enabled'] ?? false) || ($setting['assignment_mode'] ?? '') !== 'auto') {
            return null;
        }

        return $key;
    }
}
