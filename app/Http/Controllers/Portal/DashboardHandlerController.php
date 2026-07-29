<?php

namespace App\Http\Controllers\Portal;

use App\Models\Admin;
use App\Services\Portal\DashboardNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardHandlerController
{
    public function __construct(
        private readonly DashboardNotificationService $notifications,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();
        $adminId = (int) $admin->id;

        if ($request->isMethod('GET')) {
            return $this->handleGet($request, $adminId);
        }

        if ($request->isMethod('POST')) {
            return $this->handlePost($request, $adminId);
        }

        return response()->json(['success' => false, 'message' => 'Method not allowed.'], 405);
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

            return response()->json([
                'success' => true,
                'csrf_token' => csrf_token(),
                ...$payload,
            ]);
        }

        if ($action === 'bootstrap') {
            $payload = $this->notifications->getNotifications($adminId);

            return response()->json([
                'success' => true,
                'csrf_token' => csrf_token(),
                'overview' => [],
                'charts' => [],
                'activity' => [],
                'notifications' => $payload['notifications'],
                'unread_count' => $payload['unread_count'],
                'latest_id' => $payload['latest_id'],
                'last_read_id' => $payload['last_read_id'],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unknown action.'], 400);
    }

    private function handlePost(Request $request, int $adminId): JsonResponse
    {
        $action = (string) $request->input('action', '');

        if ($action === 'mark_notifications_read') {
            $lastLogId = (int) $request->input('last_log_id', 0);
            $this->notifications->markNotificationsRead($adminId, $lastLogId);
            $payload = $this->notifications->getNotifications($adminId);

            return response()->json([
                'success' => true,
                'message' => 'Notifications marked as read.',
                'unread_count' => $payload['unread_count'],
                'last_read_id' => $payload['last_read_id'],
                'latest_id' => $payload['latest_id'],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unknown action.'], 400);
    }
}
