<?php

namespace App\Http\Controllers\Website;

use App\Http\Requests\Website\SavePromotionRequest;
use App\Models\Admin;
use App\Models\WebsitePromotion;
use App\Policies\WebsitePolicy;
use App\Services\Website\PromotionReadService;
use App\Services\Website\PromotionWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

final class PromotionController
{
    public function __construct(
        private readonly PromotionReadService $read,
        private readonly PromotionWriteService $write,
        private readonly WebsitePolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewWebsite($admin);

        return view('website.promotions.index', [
            'promotions' => $this->read->list(),
            'canManage' => $this->policy->manageWebsite($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageWebsite($this->admin());

        return view('website.promotions.create', [
            'promotion' => [
                'title' => '',
                'eyebrow' => '',
                'body' => '',
                'cta_label' => 'Learn more',
                'cta_url' => 'event',
                'starts_at' => null,
                'ends_at' => null,
                'sort_order' => 10,
                'is_active' => true,
                'show_every_visit' => true,
                'image_url' => null,
            ],
        ]);
    }

    public function store(SavePromotionRequest $request): RedirectResponse
    {
        $this->policy->requireManageWebsite($this->admin());

        try {
            $this->write->save(
                $this->payload($request),
                $request->file('image'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('website.promotions.index')->with('status', 'Promotion banner saved. It will appear as soon as the public site loads.');
    }

    public function edit(WebsitePromotion $promotion): View
    {
        $this->policy->requireManageWebsite($this->admin());
        $data = $this->read->find((int) $promotion->id);
        abort_unless($data !== null, 404);

        return view('website.promotions.edit', ['promotion' => $data]);
    }

    public function update(SavePromotionRequest $request, WebsitePromotion $promotion): RedirectResponse
    {
        $this->policy->requireManageWebsite($this->admin());

        try {
            $this->write->save(
                $this->payload($request) + ['id' => (int) $promotion->id],
                $request->file('image'),
                $request->boolean('remove_image'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('website.promotions.index')->with('status', 'Promotion banner updated.');
    }

    public function destroy(WebsitePromotion $promotion): RedirectResponse
    {
        $this->policy->requireManageWebsite($this->admin());
        $this->write->delete((int) $promotion->id);

        return redirect()->route('website.promotions.index')->with('status', 'Promotion banner removed.');
    }

    /** @return array<string, mixed> */
    private function payload(SavePromotionRequest $request): array
    {
        return [
            'title' => $request->validated('title'),
            'eyebrow' => $request->validated('eyebrow'),
            'body' => $request->validated('body'),
            'cta_label' => $request->validated('cta_label'),
            'cta_url' => $request->validated('cta_url'),
            'starts_at' => $request->validated('starts_at'),
            'ends_at' => $request->validated('ends_at'),
            'sort_order' => $request->integer('sort_order'),
            'is_active' => $request->boolean('is_active'),
            'show_every_visit' => $request->boolean('show_every_visit'),
        ];
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
