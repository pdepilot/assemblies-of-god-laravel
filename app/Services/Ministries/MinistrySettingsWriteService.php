<?php

namespace App\Services\Ministries;

use App\Models\MinistrySetting;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class MinistrySettingsWriteService
{
    public function __construct(
        private readonly MinistrySettingsReadService $read,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        $setting = MinistrySetting::query()->find($id);
        if (! $setting) {
            throw new InvalidArgumentException('Ministry setting not found.');
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Ministry name is required.');
        }

        $genderFilter = (string) ($data['gender_filter'] ?? 'any');
        if (! in_array($genderFilter, ['any', 'male', 'female'], true)) {
            throw new InvalidArgumentException('Invalid gender filter.');
        }

        $assignmentMode = (string) ($data['assignment_mode'] ?? 'manual');
        if (! in_array($assignmentMode, ['auto', 'manual'], true)) {
            throw new InvalidArgumentException('Invalid assignment mode.');
        }

        $sortOrder = max(0, (int) ($data['sort_order'] ?? 0));

        $setting->update([
            'name' => $name,
            'min_age' => isset($data['min_age']) && $data['min_age'] !== '' ? (int) $data['min_age'] : null,
            'max_age' => isset($data['max_age']) && $data['max_age'] !== '' ? (int) $data['max_age'] : null,
            'gender_filter' => $genderFilter,
            'assignment_mode' => $assignmentMode,
            'is_enabled' => ! empty($data['is_enabled']),
            'sort_order' => $sortOrder,
        ]);

        return $this->read->getByKey((string) $setting->ministry_key) ?? $setting->fresh()->toArray();
    }
}
