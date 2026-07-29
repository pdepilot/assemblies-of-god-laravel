<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SdtgRegistrationWriteService
{
    /** @param array<string, mixed> $data */
    public function updateStatusAndNotes(int $id, array $data): void
    {
        $status = (string) ($data['status'] ?? '');
        if ($status !== '' && ! in_array($status, SdtgRegistrationReadService::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid registration status.');
        }

        $payload = [];
        if ($status !== '') {
            $payload['status'] = $status;
        }
        if (array_key_exists('notes', $data)) {
            $payload['notes'] = (string) $data['notes'];
        }

        if ($payload === []) {
            return;
        }

        DB::table('sdtg_registrations')->where('id', $id)->update($payload);
    }
}
