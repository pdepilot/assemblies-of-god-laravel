<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class WorshipWriteService
{
    public function __construct(
        private readonly WorshipReadService $read,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $programs
     * @param  array<string, mixed>|null  $location
     * @return list<array<string, mixed>>
     */
    public function save(array $programs, int $adminId, ?array $location = null): array
    {
        if (! Schema::hasTable('ag_site_content')) {
            throw new RuntimeException('Website content table is not available.');
        }

        $normalized = [];
        foreach ($programs as $item) {
            if (! is_array($item)) {
                continue;
            }
            $row = $this->read->normalize($item);
            if ($row['title'] === '' && $row['day'] === '' && $row['time_primary'] === '' && $row['body'] === '') {
                continue;
            }
            $normalized[] = $row;
        }

        $locationRow = $location !== null
            ? $this->read->normalizeLocation($location)
            : $this->read->storedPayload()['location'];

        $json = json_encode([
            'programs' => $normalized,
            'location' => $locationRow,
        ], JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Unable to encode worship programmes.');
        }

        $existing = DB::table('ag_site_content')->where('section_key', WorshipReadService::SECTION_KEY)->first();
        $payload = [
            'content_json' => $json,
            'updated_by' => $adminId > 0 ? $adminId : null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('ag_site_content')->where('section_key', WorshipReadService::SECTION_KEY)->update($payload);
        } else {
            DB::table('ag_site_content')->insert($payload + [
                'section_key' => WorshipReadService::SECTION_KEY,
                'created_at' => now(),
            ]);
        }

        return $this->read->programs();
    }
}
