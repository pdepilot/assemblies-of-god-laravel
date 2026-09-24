<?php

namespace App\Http\Controllers\Portal;

use App\Models\Admin;
use App\Services\Portal\DashboardNotificationService;
use App\Services\Portal\DashboardReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class DashboardHandlerController
{
    public function __construct(
        private readonly DashboardNotificationService $notifications,
        private readonly DashboardReadService $dashboard,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        if (! $admin instanceof Admin) {
            return $this->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        try {
            $adminId = (int) $admin->id;

            if ($request->isMethod('GET')) {
                return $this->handleGet($request, $adminId);
            }

            if ($request->isMethod('POST')) {
                return $this->handlePost($request, $adminId);
            }

            return $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        } catch (Throwable $e) {
            report($e);

            return $this->json([
                'success' => false,
                'message' => 'Unable to load dashboard data.',
            ], 200);
        }
    }

    private function handleGet(Request $request, int $adminId): JsonResponse
    {
        $action = (string) $request->query('action', 'bootstrap');

        if ($action === 'notifications') {
            $sinceId = $request->query('since_id');
            $limit = max(1, min(50, (int) $request->query('limit', 12)));
            $payload = $this->notifications->getNotifications(
                $adminId,
                $limit,
                $sinceId !== null && $sinceId !== '' ? (int) $sinceId : null,
            );

            return $this->json([
                'success' => true,
                'csrf_token' => csrf_token(),
            ] + $payload);
        }

        if ($action === 'overview') {
            return $this->json([
                'success' => true,
                'overview' => $this->overviewPayload(),
            ]);
        }

        if ($action === 'charts') {
            return $this->json([
                'success' => true,
                'charts' => $this->chartsPayload(),
            ]);
        }

        if ($action === 'activity') {
            $limit = max(1, min(25, (int) $request->query('limit', 8)));

            return $this->json([
                'success' => true,
                'activity' => $this->activityPayload($adminId, $limit),
            ]);
        }

        if ($action === 'bootstrap') {
            $payload = $this->notifications->getNotifications($adminId);

            return $this->json([
                'success' => true,
                'csrf_token' => csrf_token(),
                'overview' => $this->overviewPayload(),
                'charts' => $this->chartsPayload(),
                'activity' => $this->activityPayload($adminId, 8),
                'notifications' => $payload['notifications'],
                'unread_count' => $payload['unread_count'],
                'latest_id' => $payload['latest_id'],
                'last_read_id' => $payload['last_read_id'],
            ]);
        }

        return $this->json(['success' => false, 'message' => 'Unknown action.'], 400);
    }

    private function handlePost(Request $request, int $adminId): JsonResponse
    {
        $action = (string) $request->input('action', '');

        if ($action === 'mark_notifications_read') {
            $lastLogId = (int) $request->input('last_log_id', 0);
            $this->notifications->markNotificationsRead($adminId, $lastLogId);
            $payload = $this->notifications->getNotifications($adminId);

            return $this->json([
                'success' => true,
                'message' => 'Notifications marked as read.',
                'unread_count' => $payload['unread_count'],
                'last_read_id' => $payload['last_read_id'],
                'latest_id' => $payload['latest_id'],
            ]);
        }

        return $this->json(['success' => false, 'message' => 'Unknown action.'], 400);
    }

    /** @return array<string, mixed> */
    private function overviewPayload(): array
    {
        $stats = $this->dashboard->summary();

        return [
            'ag' => [
                'total_members' => (int) ($stats['members'] ?? 0),
                'total_visitors' => (int) ($stats['visitors'] ?? 0),
                'weekly_attendance' => 0,
                'visitors_ready' => 0,
                'upcoming_events' => (int) ($stats['events'] ?? 0),
                'member_trend' => 0,
                'visitor_trend' => 0,
            ],
            'finance' => [
                'donations_month' => (float) ($stats['donations_month'] ?? 0),
                'donation_trend' => 0,
            ],
            'ministries' => [
                'youths' => ['members' => 0],
            ],
            'sunday_school' => [
                'total_students' => (int) ($stats['ss_students'] ?? 0),
                'total_teachers' => 0,
                'attendance_today' => 0,
            ],
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function chartsPayload(): array
    {
        return [
            'engagement' => [
                'labels' => [],
                'members' => [],
                'visitors' => [],
                'attendance' => [],
            ],
            'registrations' => [
                'labels' => [],
                'values' => [],
            ],
            'breakdown' => [
                'labels' => [],
                'values' => [],
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function activityPayload(int $adminId, int $limit): array
    {
        $payload = $this->notifications->getNotifications($adminId, $limit);

        return array_map(static function (array $item): array {
            return [
                'title' => (string) ($item['title'] ?? 'Update'),
                'text' => (string) ($item['text'] ?? ''),
                'time' => (string) ($item['time'] ?? ''),
                'icon' => (string) ($item['icon'] ?? 'blue'),
            ];
        }, $payload['notifications']);
    }

    /** @param array<string, mixed> $data */
    private function json(array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse(
            $data,
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8'],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES,
        );
    }
}
