<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Requests\Analytics\PurgeTrafficRequest;
use App\Models\Admin;
use App\Policies\AnalyticsPolicy;
use App\Services\Analytics\SiteTrafficReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

final class SiteTrafficController
{
    private const TABLE_PER_PAGE = 5;

    public function __construct(
        private readonly SiteTrafficReadService $traffic,
        private readonly AnalyticsPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewTraffic($admin);

        $from = (string) $request->query('from', now()->subDays(29)->format('Y-m-d'));
        $to = (string) $request->query('to', now()->format('Y-m-d'));
        $siteArea = (string) $request->query('site_area', 'all');
        $pagesPage = max(1, (int) $request->query('pages_page', 1));
        $sessionsPage = max(1, (int) $request->query('sessions_page', 1));

        $dashboard = $this->traffic->getDashboard($from, $to, $siteArea);
        $filterQuery = [
            'from' => $dashboard['filters']['from'],
            'to' => $dashboard['filters']['to'],
            'site_area' => $dashboard['filters']['site_area'],
        ];

        return view('analytics.site-traffic.index', [
            'dashboard' => $dashboard,
            'topPages' => $this->paginateList(
                $dashboard['top_pages'] ?? [],
                $pagesPage,
                'pages_page',
                $filterQuery + ['sessions_page' => $sessionsPage],
            ),
            'recentSessions' => $this->paginateList(
                $dashboard['recent_sessions'] ?? [],
                $sessionsPage,
                'sessions_page',
                $filterQuery + ['pages_page' => $pagesPage],
            ),
            'canManage' => $this->policy->manageTraffic($admin),
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $admin = $this->admin();
        $this->policy->requireViewTraffic($admin);

        return response()->json($this->traffic->getDashboard(
            (string) $request->query('from', ''),
            (string) $request->query('to', ''),
            (string) $request->query('site_area', 'all'),
        ));
    }

    public function session(string $sessionKey): JsonResponse
    {
        $admin = $this->admin();
        $this->policy->requireViewTraffic($admin);

        $detail = $this->traffic->getSessionDetail($sessionKey);
        if ($detail === null) {
            return response()->json(['message' => 'Session not found.'], 404);
        }

        return response()->json($detail);
    }

    public function visitor(Request $request, string $visitorKey): JsonResponse
    {
        $admin = $this->admin();
        $this->policy->requireViewTraffic($admin);

        return response()->json($this->traffic->getVisitorDetail(
            $visitorKey,
            (string) $request->query('from', ''),
            (string) $request->query('to', ''),
        ));
    }

    public function purge(PurgeTrafficRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageTraffic($admin);

        $result = $this->traffic->purgeOlderThan((int) $request->validated('days'));

        return redirect()
            ->route('analytics.site-traffic.index')
            ->with('status', sprintf(
                'Purged %d sessions and related pageviews older than %d days.',
                $result['deleted_sessions'],
                $result['retention_days']
            ));
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $query
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginateList(array $items, int $page, string $pageName, array $query): LengthAwarePaginator
    {
        $total = count($items);
        $lastPage = max(1, (int) ceil($total / self::TABLE_PER_PAGE));
        $page = min($page, $lastPage);
        $slice = array_values(array_slice($items, ($page - 1) * self::TABLE_PER_PAGE, self::TABLE_PER_PAGE));

        return (new LengthAwarePaginator(
            $slice,
            $total,
            self::TABLE_PER_PAGE,
            $page,
            [
                'path' => route('analytics.site-traffic.index'),
                'pageName' => $pageName,
            ]
        ))->appends($query);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
