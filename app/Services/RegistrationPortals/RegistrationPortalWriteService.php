<?php

namespace App\Services\RegistrationPortals;

use App\Models\RegistrationPortal;
use App\Models\Registrant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RegistrationPortalWriteService
{
    public function __construct(
        private readonly RegistrationPortalReadService $read,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function savePortal(array $data, int $adminId): array
    {
        $id = (int) ($data['id'] ?? 0);
        $eventName = trim((string) ($data['event_name'] ?? ''));
        if ($eventName === '') {
            throw new InvalidArgumentException('Event name is required.');
        }

        $slug = $this->normalizeSlug((string) ($data['slug'] ?? $eventName));
        $this->assertSlugAvailable($slug, $id);

        $template = strtolower(trim((string) ($data['template'] ?? 'conference')));
        if (! in_array($template, ['conference', 'youth', 'quick'], true)) {
            $template = 'conference';
        }

        $category = trim((string) ($data['category'] ?? ''));
        if ($category === '') {
            $category = match ($template) {
                'youth' => 'Youth',
                'quick' => 'Event',
                default => 'Conference',
            };
        }

        $payload = [
            'slug' => $slug,
            'event_name' => $eventName,
            'event_subtitle' => trim((string) ($data['event_subtitle'] ?? '')) ?: null,
            'category' => $category,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'theme' => trim((string) ($data['theme'] ?? '')) ?: null,
            'start_date' => $this->nullableDate($data['start_date'] ?? null),
            'end_date' => $this->nullableDate($data['end_date'] ?? null),
            'registration_opens' => $this->nullableDateTime($data['registration_opens'] ?? null),
            'registration_closes' => $this->nullableDateTime($data['registration_closes'] ?? null),
            'venue' => trim((string) ($data['venue'] ?? '')) ?: null,
            'contact_email' => trim((string) ($data['contact_email'] ?? '')) ?: null,
            'contact_phone' => trim((string) ($data['contact_phone'] ?? '')) ?: null,
            'max_registrants' => isset($data['max_registrants']) && $data['max_registrants'] !== ''
                ? max(0, (int) $data['max_registrants']) : null,
            'status' => $this->normalizePortalStatus((string) ($data['status'] ?? 'draft')),
            'landing_config' => $this->defaultLandingConfig($eventName),
            'registration_settings' => $this->defaultRegistrationSettings(),
        ];

        $customFields = $this->normalizeIncomingFields($data['fields'] ?? null);
        $isNew = $id < 1;

        if (! $isNew) {
            $portal = RegistrationPortal::query()->findOrFail($id);
            $payload['registration_settings'] = $portal->registration_settings ?? $this->defaultRegistrationSettings();
            $payload['landing_config'] = $portal->landing_config ?? $this->defaultLandingConfig($eventName);
            $portal->update($payload);
        } else {
            $portal = RegistrationPortal::query()->create([
                ...$payload,
                'created_by' => $adminId,
            ]);
            $id = (int) $portal->id;
        }

        if ($customFields !== []) {
            $this->replaceFields($id, $customFields);
        } elseif ($isNew) {
            $this->seedDefaultFields($id, $template);
        }

        return $this->read->getPortal($id) ?? [];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function templateCatalog(): array
    {
        return [
            'conference' => $this->fieldsForTemplate('conference'),
            'youth' => $this->fieldsForTemplate('youth'),
            'quick' => $this->fieldsForTemplate('quick'),
        ];
    }

    /** @return array<string, mixed> */
    public function updatePortalStatus(int $id, string $status): array
    {
        $status = $this->normalizePortalStatus($status);
        RegistrationPortal::query()->where('id', $id)->update(['status' => $status]);

        return $this->read->getPortal($id) ?? [];
    }

    public function deletePortal(int $id): void
    {
        $portal = RegistrationPortal::query()->find($id);
        if (! $portal) {
            throw new InvalidArgumentException('Registration portal not found.');
        }

        DB::transaction(function () use ($id, $portal): void {
            $registrantIds = DB::table('registrants')->where('portal_id', $id)->pluck('id');
            if ($registrantIds->isNotEmpty()) {
                DB::table('registrant_answers')->whereIn('registrant_id', $registrantIds)->delete();
                DB::table('registrants')->where('portal_id', $id)->delete();
            }

            DB::table('registration_fields')->where('portal_id', $id)->delete();
            $portal->delete();
        });
    }

    /** @return array<string, mixed> */
    public function updateRegistrantStatus(int $id, string $status, int $adminId): array
    {
        if (! in_array($status, RegistrationPortalReadService::REGISTRANT_STATUSES, true)) {
            throw new InvalidArgumentException('Invalid status.');
        }

        $registrant = Registrant::query()->find($id);
        if (! $registrant) {
            throw new InvalidArgumentException('Registrant not found.');
        }

        $registrant->update([
            'status' => $status,
            'approved_by' => in_array($status, ['approved', 'rejected'], true) ? $adminId : null,
            'approved_at' => in_array($status, ['approved', 'rejected'], true) ? now() : null,
        ]);

        $row = DB::table('registrants as r')
            ->join('registration_portals as p', 'p.id', '=', 'r.portal_id')
            ->where('r.id', $id)
            ->select(['r.*', 'p.event_name', 'p.slug as portal_slug'])
            ->first();

        return $row ? (array) $row : $registrant->fresh()->toArray();
    }

    public function deleteRegistrant(int $id, int $portalId): void
    {
        $registrant = Registrant::query()->find($id);
        if (! $registrant || (int) $registrant->portal_id !== $portalId) {
            throw new InvalidArgumentException('Registrant not found.');
        }

        DB::table('registrant_answers')->where('registrant_id', $id)->delete();
        $registrant->delete();
    }

    /** @return array<string, mixed> */
    private function defaultLandingConfig(string $eventName = ''): array
    {
        return [
            'hero_enabled' => true,
            'hero_title' => $eventName,
            'hero_subtitle' => '',
            'about_enabled' => true,
            'about_content' => '',
            'footer_text' => '',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fieldsForTemplate(string $template): array
    {
        $commonContact = [
            ['field_key' => 'full_name', 'field_type' => 'text', 'label' => 'Full Name', 'is_required' => true, 'field_width' => 'half', 'options_json' => []],
            ['field_key' => 'email', 'field_type' => 'email', 'label' => 'Email Address', 'is_required' => true, 'field_width' => 'half', 'options_json' => []],
            ['field_key' => 'phone', 'field_type' => 'phone', 'label' => 'Phone Number', 'is_required' => true, 'field_width' => 'half', 'options_json' => []],
        ];

        if ($template === 'youth') {
            return [
                ...$commonContact,
                ['field_key' => 'church', 'field_type' => 'text', 'label' => 'Home Church', 'is_required' => true, 'field_width' => 'half', 'options_json' => []],
                ['field_key' => 'gender', 'field_type' => 'radio', 'label' => 'Gender', 'is_required' => true, 'field_width' => 'half', 'options_json' => ['Male', 'Female']],
                ['field_key' => 'age_group', 'field_type' => 'select', 'label' => 'Age group', 'is_required' => true, 'field_width' => 'half', 'options_json' => ['Child', 'Teen', 'Adult']],
                ['field_key' => 'age', 'field_type' => 'number', 'label' => 'Age', 'is_required' => false, 'field_width' => 'half', 'options_json' => []],
                ['field_key' => 'notes', 'field_type' => 'textarea', 'label' => 'Medical / Special Notes', 'is_required' => false, 'field_width' => 'full', 'options_json' => []],
            ];
        }

        if ($template === 'quick') {
            return [
                ...$commonContact,
                ['field_key' => 'gender', 'field_type' => 'radio', 'label' => 'Gender', 'is_required' => true, 'field_width' => 'half', 'options_json' => ['Male', 'Female']],
                ['field_key' => 'age_group', 'field_type' => 'select', 'label' => 'Age group', 'is_required' => true, 'field_width' => 'half', 'options_json' => ['Child', 'Teen', 'Adult']],
            ];
        }

        return [
            ...$commonContact,
            ['field_key' => 'church', 'field_type' => 'text', 'label' => 'Church / Assembly', 'is_required' => true, 'field_width' => 'half', 'options_json' => []],
            ['field_key' => 'state', 'field_type' => 'text', 'label' => 'State', 'is_required' => false, 'field_width' => 'half', 'options_json' => []],
            ['field_key' => 'gender', 'field_type' => 'radio', 'label' => 'Gender', 'is_required' => true, 'field_width' => 'half', 'options_json' => ['Male', 'Female']],
            ['field_key' => 'age_group', 'field_type' => 'select', 'label' => 'Age group', 'is_required' => true, 'field_width' => 'half', 'options_json' => ['Child', 'Teen', 'Adult']],
        ];
    }

    private function seedDefaultFields(int $portalId, string $template = 'conference'): void
    {
        $this->replaceFields($portalId, $this->fieldsForTemplate($template));
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     */
    private function replaceFields(int $portalId, array $fields): void
    {
        DB::table('registration_fields')->where('portal_id', $portalId)->delete();

        foreach (array_values($fields) as $index => $field) {
            $key = $this->normalizeFieldKey((string) ($field['field_key'] ?? $field['label'] ?? 'field_'.$index));
            $type = (string) ($field['field_type'] ?? 'text');
            $options = $field['options'] ?? $field['options_json'] ?? [];
            if (is_string($options)) {
                $options = array_values(array_filter(array_map('trim', explode(',', $options))));
            }
            if (! is_array($options)) {
                $options = [];
            }

            DB::table('registration_fields')->insert([
                'portal_id' => $portalId,
                'field_key' => $key,
                'field_type' => $type,
                'label' => trim((string) ($field['label'] ?? $key)) ?: $key,
                'placeholder' => trim((string) ($field['placeholder'] ?? '')) ?: null,
                'help_text' => trim((string) ($field['help_text'] ?? '')) ?: null,
                'is_required' => ! empty($field['is_required']) ? 1 : 0,
                'validation_rules' => json_encode([]),
                'default_value' => null,
                'field_width' => in_array(($field['field_width'] ?? 'full'), ['full', 'half', 'third'], true)
                    ? (string) ($field['field_width'] ?? 'full')
                    : 'full',
                'options_json' => json_encode(array_values($options)),
                'sort_order' => $index,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeIncomingFields(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $field) {
            if (! is_array($field)) {
                continue;
            }
            $label = trim((string) ($field['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $out[] = $field;
        }

        return $out;
    }

    private function normalizeFieldKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_]+/', '_', $key) ?? '';
        $key = trim($key, '_');

        return $key !== '' ? $key : 'field_'.Str::random(4);
    }

    public function ensureDefaultFields(int $portalId): void
    {
        if (DB::table('registration_fields')->where('portal_id', $portalId)->exists()) {
            return;
        }

        $this->seedDefaultFields($portalId, 'conference');
    }

    /** @return array<string, mixed> */
    private function defaultRegistrationSettings(): array
    {
        return [
            'registration_type' => 'free',
            'confirmation_method' => 'automatic',
            'waiting_list' => false,
            'duplicate_email' => true,
            'duplicate_phone' => true,
            'enable_qr_code' => true,
            'enable_email_confirmation' => true,
            'enable_attendance' => true,
        ];
    }

    private function normalizePortalStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return in_array($status, RegistrationPortalReadService::PORTAL_STATUSES, true)
            ? $status
            : 'draft';
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'event-'.Str::random(6);
    }

    private function assertSlugAvailable(string $slug, int $excludeId = 0): void
    {
        $query = DB::table('registration_portals')->where('slug', $slug);
        if ($excludeId > 0) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException('This portal slug is already in use.');
        }
    }

    private function nullableDate(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    private function nullableDateTime(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
