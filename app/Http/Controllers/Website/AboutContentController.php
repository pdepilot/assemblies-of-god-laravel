<?php

namespace App\Http\Controllers\Website;

use App\Models\Admin;
use App\Policies\WebsitePolicy;
use App\Services\Website\ChurchContentReadService;
use App\Services\Website\ChurchContentWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use InvalidArgumentException;

final class AboutContentController
{
    public function __construct(
        private readonly ChurchContentReadService $read,
        private readonly ChurchContentWriteService $write,
        private readonly WebsitePolicy $policy,
    ) {}

    public function edit(): View
    {
        $this->policy->requireManageWebsite($this->admin());
        $content = $this->read->getSiteContent();

        return view('website.about.edit', [
            'homepage' => $content['homepage_about'] ?? [],
            'aboutPage' => $content['about_page'] ?? [],
            'activeTab' => request()->query('tab', 'homepage_about'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageWebsite($admin);

        $section = (string) $request->input('section', '');
        if (! in_array($section, ChurchContentReadService::SECTIONS, true)) {
            return back()->withInput()->withErrors(['section' => 'Unknown content section.']);
        }

        $payload = is_array($request->input('content')) ? $request->input('content') : [];
        if (isset($payload['features_text'])) {
            $payload['features'] = preg_split('/\r\n|\r|\n/', (string) $payload['features_text']) ?: [];
            unset($payload['features_text']);
        }

        try {
            $this->write->saveSection(
                $section,
                $payload,
                (int) $admin->id,
                $this->uploadsFromRequest($request),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['content' => $e->getMessage()]);
        }

        $label = $section === 'homepage_about' ? 'Homepage about section saved.' : 'About page content saved.';

        return redirect()
            ->route('website.about.edit', ['tab' => $section])
            ->with('status', $label);
    }

    public function reset(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageWebsite($admin);

        $section = (string) $request->input('section', '');
        if (! in_array($section, ChurchContentReadService::SECTIONS, true)) {
            return back()->withErrors(['section' => 'Unknown content section.']);
        }

        try {
            $this->write->resetSection($section, (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['content' => $e->getMessage()]);
        }

        return redirect()
            ->route('website.about.edit', ['tab' => $section])
            ->with('status', 'Section reset to defaults.');
    }

    /** @return array<string, UploadedFile|null> */
    private function uploadsFromRequest(Request $request): array
    {
        return [
            'gallery_0' => $request->file('content_gallery_0'),
            'gallery_1' => $request->file('content_gallery_1'),
            'gallery_2' => $request->file('content_gallery_2'),
            'highlight' => $request->file('content_highlight'),
        ];
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
