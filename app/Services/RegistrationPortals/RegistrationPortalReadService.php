<?php

namespace App\Services\RegistrationPortals;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class RegistrationPortalReadService
{
    public const PORTAL_STATUSES = ['draft', 'open', 'closed', 'archived'];

    public const REGISTRANT_STATUSES = ['pending', 'approved', 'rejected', 'waitlist'];

    /** @return array<string, mixed> */
    public function getDashboardStats(): array
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $portalCounts = DB::table('registration_portals')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as active")
            ->selectRaw("SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed")
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft")
            ->first();

        $totalRegistrants = (int) DB::table('registrants')->count();
        $approved = (int) DB::table('registrants')->where('status', 'approved')->count();
        $checkedIn = (int) DB::table('registrants')
            ->whereIn('attendance_status', ['checked_in', 'checked_out'])
            ->count();

        $hasAgeGroup = Schema::hasColumn('registrants', 'age_group');
        $hasAge = Schema::hasColumn('registrants', 'age');

        $demoQuery = DB::table('registrants')
            ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(gender,'')) IN ('male','m') THEN 1 ELSE 0 END) as male")
            ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(gender,'')) IN ('female','f') THEN 1 ELSE 0 END) as female");

        if ($hasAgeGroup && $hasAge) {
            $demoQuery
                ->selectRaw("SUM(CASE
                    WHEN LOWER(COALESCE(age_group,'')) IN ('child','children') THEN 1
                    WHEN (age_group IS NULL OR age_group = '') AND age IS NOT NULL AND age <= 12 THEN 1
                    ELSE 0 END) as children")
                ->selectRaw("SUM(CASE
                    WHEN LOWER(COALESCE(age_group,'')) IN ('teen','teens','teenager','youth') THEN 1
                    WHEN (age_group IS NULL OR age_group = '') AND age IS NOT NULL AND age BETWEEN 13 AND 19 THEN 1
                    ELSE 0 END) as teens")
                ->selectRaw("SUM(CASE
                    WHEN LOWER(COALESCE(age_group,'')) IN ('adult','adults') THEN 1
                    WHEN (age_group IS NULL OR age_group = '') AND age IS NOT NULL AND age >= 20 THEN 1
                    ELSE 0 END) as adults");
        } elseif ($hasAgeGroup) {
            $demoQuery
                ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(age_group,'')) IN ('child','children') THEN 1 ELSE 0 END) as children")
                ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(age_group,'')) IN ('teen','teens','teenager','youth') THEN 1 ELSE 0 END) as teens")
                ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(age_group,'')) IN ('adult','adults') THEN 1 ELSE 0 END) as adults");
        } else {
            $demoQuery
                ->selectRaw('0 as children')
                ->selectRaw('0 as teens')
                ->selectRaw('0 as adults');
        }

        $demographics = $demoQuery->first();

        return [
            'portals' => [
                'total' => (int) ($portalCounts->total ?? 0),
                'active' => (int) ($portalCounts->active ?? 0),
                'closed' => (int) ($portalCounts->closed ?? 0),
                'draft' => (int) ($portalCounts->draft ?? 0),
            ],
            'registrations' => [
                'today' => (int) DB::table('registrants')->whereDate('created_at', $today)->count(),
                'weekly' => (int) DB::table('registrants')->whereDate('created_at', '>=', $weekStart)->count(),
                'monthly' => (int) DB::table('registrants')->whereDate('created_at', '>=', $monthStart)->count(),
                'total' => $totalRegistrants,
            ],
            'demographics' => [
                'male' => (int) ($demographics->male ?? 0),
                'female' => (int) ($demographics->female ?? 0),
                'children' => (int) ($demographics->children ?? 0),
                'teens' => (int) ($demographics->teens ?? 0),
                'adults' => (int) ($demographics->adults ?? 0),
            ],
            'rates' => [
                'attendance' => $totalRegistrants > 0 ? round(($checkedIn / $totalRegistrants) * 100, 1) : 0,
                'approval' => $totalRegistrants > 0 ? round(($approved / $totalRegistrants) * 100, 1) : 0,
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function listPortals(string $status = '', string $query = ''): array
    {
        $base = DB::table('registration_portals as p')
            ->selectRaw('p.*, (SELECT COUNT(*) FROM registrants r WHERE r.portal_id = p.id) as registrant_count');

        if ($status !== '' && in_array($status, self::PORTAL_STATUSES, true)) {
            $base->where('p.status', $status);
        }

        if ($query !== '') {
            $like = '%'.$query.'%';
            $base->where(function ($q) use ($like) {
                $q->where('p.event_name', 'like', $like)
                    ->orWhere('p.slug', 'like', $like)
                    ->orWhere('p.category', 'like', $like);
            });
        }

        return $base
            ->orderByDesc('p.updated_at')
            ->orderByDesc('p.id')
            ->get()
            ->map(fn ($row) => $this->formatPortal((array) $row))
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function getPortal(int $id): ?array
    {
        $row = DB::table('registration_portals')->where('id', $id)->first();

        return $row ? $this->formatPortal((array) $row, true) : null;
    }

    /** @return array<string, mixed>|null */
    public function getPortalBySlug(string $slug, bool $publicOnly = true): ?array
    {
        $slug = strtolower(trim($slug));
        $row = DB::table('registration_portals')->where('slug', $slug)->first();
        if (! $row) {
            return null;
        }

        if ($publicOnly && ($row->status ?? '') === 'draft') {
            return null;
        }

        return $this->formatPortal((array) $row, true);
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function listRegistrants(int $portalId, string $status, string $query, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $base = DB::table('registrants as r')
            ->join('registration_portals as p', 'p.id', '=', 'r.portal_id')
            ->where('r.portal_id', $portalId);

        if ($status !== '' && in_array($status, self::REGISTRANT_STATUSES, true)) {
            $base->where('r.status', $status);
        }

        if ($query !== '') {
            $like = '%'.$query.'%';
            $base->where(function ($q) use ($like) {
                $q->where('r.full_name', 'like', $like)
                    ->orWhere('r.email', 'like', $like)
                    ->orWhere('r.phone', 'like', $like)
                    ->orWhere('r.registration_number', 'like', $like);
            });
        }

        $total = (int) (clone $base)->count();

        $items = (clone $base)
            ->select(['r.*', 'p.event_name', 'p.slug as portal_slug'])
            ->orderByDesc('r.created_at')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->formatRegistrant((array) $row))
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatPortal(array $row, bool $decodeJson = false): array
    {
        $settings = $row['registration_settings'] ?? null;
        $landing = $row['landing_config'] ?? null;
        if ($decodeJson) {
            if (is_string($settings)) {
                $settings = json_decode($settings, true);
            }
            if (is_string($landing)) {
                $landing = json_decode($landing, true);
            }
        }

        $slug = (string) $row['slug'];
        $portal = [
            'id' => (int) $row['id'],
            'slug' => $slug,
            'event_name' => (string) $row['event_name'],
            'event_subtitle' => $row['event_subtitle'],
            'category' => $row['category'],
            'description' => (string) ($row['description'] ?? ''),
            'theme' => $row['theme'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'registration_opens' => $row['registration_opens'],
            'registration_closes' => $row['registration_closes'],
            'venue' => $row['venue'],
            'map_link' => $row['map_link'] ?? null,
            'banner_path' => $row['banner_path'] ?? null,
            'banner_url' => ! empty($row['banner_path']) ? asset('storage/'.$row['banner_path']) : null,
            'contact_email' => $row['contact_email'],
            'contact_phone' => $row['contact_phone'],
            'max_registrants' => $row['max_registrants'] !== null ? (int) $row['max_registrants'] : null,
            'status' => (string) $row['status'],
            'registration_settings' => is_array($settings) ? $settings : [],
            'landing_config' => is_array($landing) ? $landing : [],
            'registrant_count' => (int) ($row['registrant_count'] ?? 0),
            'public_url' => url('/register/'.$slug),
            'qr_image_url' => url('/api/portal-qr.php?slug='.urlencode($slug)),
            'qr_page_url' => route('registration-portals.qr', (int) $row['id']),
        ];

        if ($decodeJson) {
            $portal['fields'] = $this->listFields((int) $row['id']);
        }

        return $portal;
    }

    /** @return list<array<string, mixed>> */
    private function listFields(int $portalId): array
    {
        return DB::table('registration_fields')
            ->where('portal_id', $portalId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                $row = (array) $row;
                $options = $row['options_json'] ?? [];
                if (is_string($options)) {
                    $options = json_decode($options, true) ?: [];
                }
                $rules = $row['validation_rules'] ?? [];
                if (is_string($rules)) {
                    $rules = json_decode($rules, true) ?: [];
                }

                return [
                    'id' => (int) $row['id'],
                    'portal_id' => (int) $row['portal_id'],
                    'field_key' => (string) $row['field_key'],
                    'field_type' => (string) $row['field_type'],
                    'label' => (string) $row['label'],
                    'placeholder' => $row['placeholder'],
                    'help_text' => $row['help_text'],
                    'is_required' => (bool) $row['is_required'],
                    'validation_rules' => is_array($rules) ? $rules : [],
                    'default_value' => $row['default_value'],
                    'field_width' => (string) ($row['field_width'] ?? 'full'),
                    'options' => is_array($options) ? $options : [],
                    'sort_order' => (int) $row['sort_order'],
                ];
            })
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function getRegistrant(int $id): ?array
    {
        $row = DB::table('registrants as r')
            ->join('registration_portals as p', 'p.id', '=', 'r.portal_id')
            ->where('r.id', $id)
            ->select(['r.*', 'p.event_name', 'p.slug as portal_slug'])
            ->first();

        if (! $row) {
            return null;
        }

        $registrant = $this->formatRegistrant((array) $row);
        $registrant['qr_token'] = (string) ($row->qr_token ?? '');
        $registrant['ip_address'] = $row->ip_address;
        $registrant['user_agent'] = $row->user_agent;
        $registrant['approved_by'] = $row->approved_by !== null ? (int) $row->approved_by : null;
        $registrant['approved_at'] = $row->approved_at;
        $registrant['updated_at'] = (string) ($row->updated_at ?? '');
        $registrant['answers'] = $this->getRegistrantAnswers($id);

        return $registrant;
    }

    /** @return list<array<string, mixed>> */
    private function getRegistrantAnswers(int $registrantId): array
    {
        return DB::table('registrant_answers as a')
            ->leftJoin('registration_fields as f', 'f.id', '=', 'a.field_id')
            ->where('a.registrant_id', $registrantId)
            ->orderBy('f.sort_order')
            ->orderBy('a.id')
            ->select([
                'a.field_key',
                'a.answer_text',
                'a.answer_file',
                'f.label as field_label',
                'f.field_type',
            ])
            ->get()
            ->map(function ($row) {
                $file = $row->answer_file;

                return [
                    'field_key' => (string) $row->field_key,
                    'label' => (string) ($row->field_label ?: $row->field_key),
                    'field_type' => (string) ($row->field_type ?? 'text'),
                    'answer_text' => $row->answer_text,
                    'answer_file' => $file,
                    'file_url' => $file ? asset('storage/'.$file) : null,
                ];
            })
            ->all();
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatRegistrant(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'portal_id' => (int) $row['portal_id'],
            'registration_number' => (string) $row['registration_number'],
            'full_name' => (string) $row['full_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'church' => $row['church'],
            'state_name' => $row['state_name'],
            'gender' => $row['gender'],
            'age_group' => $row['age_group'] ?? null,
            'age' => isset($row['age']) && $row['age'] !== null ? (int) $row['age'] : null,
            'status' => (string) $row['status'],
            'payment_status' => (string) $row['payment_status'],
            'attendance_status' => (string) $row['attendance_status'],
            'event_name' => (string) ($row['event_name'] ?? ''),
            'portal_slug' => (string) ($row['portal_slug'] ?? ''),
            'created_at' => (string) $row['created_at'],
        ];
    }
}
