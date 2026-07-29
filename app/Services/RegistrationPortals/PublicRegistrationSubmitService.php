<?php

namespace App\Services\RegistrationPortals;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class PublicRegistrationSubmitService
{
    public function __construct(
        private readonly RegistrationPortalReadService $read,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, UploadedFile|array|null>  $files
     * @return array<string, mixed>
     */
    public function submit(string $slug, array $input, array $files, ?string $ip, ?string $userAgent): array
    {
        $portal = $this->read->getPortalBySlug($slug, true);
        if ($portal === null) {
            throw new InvalidArgumentException('Event not found.');
        }
        if (($portal['status'] ?? '') !== 'open') {
            throw new InvalidArgumentException('Registration is not open for this event.');
        }

        $settings = is_array($portal['registration_settings'] ?? null)
            ? $portal['registration_settings']
            : [];

        $this->assertRegistrationWindow($portal);
        $this->assertCapacity($portal, $settings);

        $fields = $portal['fields'] ?? [];
        $answers = [];
        $fullName = '';
        $email = null;
        $phone = null;
        $church = null;
        $stateName = null;
        $gender = null;
        $ageGroup = null;
        $age = null;

        foreach ($fields as $field) {
            $key = (string) $field['field_key'];
            $type = (string) $field['field_type'];
            $required = ! empty($field['is_required']);
            $label = (string) ($field['label'] ?? $key);

            if (in_array($type, ['file', 'passport'], true)) {
                $file = $files[$key] ?? null;
                $uploaded = $file instanceof UploadedFile ? $file : null;
                if ($required && ($uploaded === null || ! $uploaded->isValid())) {
                    throw new InvalidArgumentException($label.' is required.');
                }
                if ($uploaded && $uploaded->isValid()) {
                    $stored = $uploaded->store('registration-portals/'.$portal['id'].'/documents', 'public');
                    $answers[] = [
                        'field_id' => (int) $field['id'],
                        'field_key' => $key,
                        'answer_text' => null,
                        'answer_file' => $stored,
                    ];
                }
                continue;
            }

            $raw = $input[$key] ?? null;
            $value = is_array($raw) ? implode(', ', $raw) : trim((string) ($raw ?? ''));
            if ($required && $value === '') {
                throw new InvalidArgumentException($label.' is required.');
            }
            if ($type === 'email' && $value !== '' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Please enter a valid email address.');
            }

            $answers[] = [
                'field_id' => (int) $field['id'],
                'field_key' => $key,
                'answer_text' => $value !== '' ? $value : null,
                'answer_file' => null,
            ];

            $labelLower = strtolower($label);
            if ($fullName === '' && ($key === 'full_name' || str_contains($labelLower, 'full name') || $key === 'name')) {
                $fullName = $value;
            }
            if ($email === null && ($type === 'email' || $key === 'email')) {
                $email = $value !== '' ? $value : null;
            }
            if ($phone === null && ($type === 'phone' || $key === 'phone')) {
                $phone = $value !== '' ? $value : null;
            }
            if ($church === null && ($type === 'church' || $key === 'church')) {
                $church = $value !== '' ? $value : null;
            }
            if ($stateName === null && ($type === 'state' || $key === 'state')) {
                $stateName = $value !== '' ? $value : null;
            }
            if ($gender === null && $key === 'gender') {
                $gender = $value !== '' ? $value : null;
            }
            if ($ageGroup === null && ($key === 'age_group' || str_contains($labelLower, 'age group'))) {
                $ageGroup = $this->normalizeAgeGroup($value);
            }
            if ($age === null && $key === 'age' && is_numeric($value)) {
                $age = max(0, min(120, (int) $value));
            }
        }

        if ($fullName === '') {
            $fullName = trim((string) ($input['full_name'] ?? $input['name'] ?? 'Registrant'));
        }

        if ($ageGroup === null && $age !== null) {
            $ageGroup = $this->ageGroupFromAge($age);
        }

        if (! empty($settings['duplicate_email']) && $email) {
            $this->assertUniqueContact((int) $portal['id'], 'email', $email);
        }
        if (! empty($settings['duplicate_phone']) && $phone) {
            $this->assertUniqueContact((int) $portal['id'], 'phone', $phone);
        }

        $approval = ($settings['confirmation_method'] ?? 'automatic') === 'automatic' ? 'approved' : 'pending';
        if (! empty($settings['waiting_list']) && $this->isAtCapacity($portal)) {
            $approval = 'waitlist';
        }

        $regNumber = 'REG-'.$portal['id'].'-'.strtoupper(bin2hex(random_bytes(3)));
        $qrToken = bin2hex(random_bytes(16));

        return DB::transaction(function () use (
            $portal, $regNumber, $fullName, $email, $phone, $church, $stateName, $gender,
            $ageGroup, $age, $approval, $settings, $qrToken, $ip, $userAgent, $answers
        ) {
            $payload = [
                'portal_id' => (int) $portal['id'],
                'registration_number' => $regNumber,
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'church' => $church,
                'state_name' => $stateName,
                'gender' => $gender,
                'status' => $approval,
                'payment_status' => ($settings['registration_type'] ?? 'free') === 'paid' ? 'pending' : 'free',
                'attendance_status' => 'not_checked_in',
                'qr_token' => $qrToken,
                'ip_address' => $ip,
                'user_agent' => $userAgent ? Str::limit($userAgent, 500, '') : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('registrants', 'age_group')) {
                $payload['age_group'] = $ageGroup;
            }
            if (Schema::hasColumn('registrants', 'age')) {
                $payload['age'] = $age;
            }

            $registrantId = DB::table('registrants')->insertGetId($payload);

            foreach ($answers as $answer) {
                DB::table('registrant_answers')->insert([
                    'registrant_id' => $registrantId,
                    'field_id' => $answer['field_id'],
                    'field_key' => $answer['field_key'],
                    'answer_text' => $answer['answer_text'],
                    'answer_file' => $answer['answer_file'],
                ]);
            }

            return [
                'id' => $registrantId,
                'portal_id' => (int) $portal['id'],
                'registration_number' => $regNumber,
                'full_name' => $fullName,
                'status' => $approval,
            ];
        });
    }

    /** @param array<string, mixed> $portal */
    private function assertRegistrationWindow(array $portal): void
    {
        $now = now();
        if (! empty($portal['registration_opens'])) {
            $opens = \Illuminate\Support\Carbon::parse((string) $portal['registration_opens']);
            if ($now->lt($opens)) {
                throw new InvalidArgumentException('Registration has not opened yet.');
            }
        }
        if (! empty($portal['registration_closes'])) {
            $closes = \Illuminate\Support\Carbon::parse((string) $portal['registration_closes']);
            if ($now->gt($closes)) {
                throw new InvalidArgumentException('Registration has closed.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $portal
     * @param  array<string, mixed>  $settings
     */
    private function assertCapacity(array $portal, array $settings): void
    {
        if ($this->isAtCapacity($portal) && empty($settings['waiting_list'])) {
            throw new InvalidArgumentException('This event has reached maximum capacity.');
        }
    }

    /** @param array<string, mixed> $portal */
    private function isAtCapacity(array $portal): bool
    {
        $max = $portal['max_registrants'] ?? null;
        if (! $max) {
            return false;
        }

        $count = (int) DB::table('registrants')
            ->where('portal_id', (int) $portal['id'])
            ->whereIn('status', ['approved', 'pending'])
            ->count();

        return $count >= (int) $max;
    }

    private function assertUniqueContact(int $portalId, string $field, string $value): void
    {
        $column = $field === 'phone' ? 'phone' : 'email';
        $exists = DB::table('registrants')
            ->where('portal_id', $portalId)
            ->where($column, $value)
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException('A registration with this '.$field.' already exists.');
        }
    }

    private function normalizeAgeGroup(string $value): ?string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return null;
        }

        return match (true) {
            in_array($value, ['child', 'children', 'kid', 'kids'], true) => 'Child',
            in_array($value, ['teen', 'teens', 'teenager', 'youth'], true) => 'Teen',
            in_array($value, ['adult', 'adults'], true) => 'Adult',
            default => ucfirst($value),
        };
    }

    private function ageGroupFromAge(int $age): string
    {
        if ($age <= 12) {
            return 'Child';
        }
        if ($age <= 19) {
            return 'Teen';
        }

        return 'Adult';
    }
}
