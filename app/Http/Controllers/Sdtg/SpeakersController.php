<?php

namespace App\Http\Controllers\Sdtg;

use App\Http\Controllers\Concerns\ResolvesSdtgAdmin;
use App\Http\Requests\Sdtg\SaveSdtgSpeakerRequest;
use App\Models\Admin;
use App\Models\SdtgSpeaker;
use App\Policies\SdtgPolicy;
use App\Services\Sdtg\SdtgSpeakerReadService;
use App\Services\Sdtg\SdtgSpeakerWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class SpeakersController
{
    use ResolvesSdtgAdmin;

    public function __construct(
        private readonly SdtgSpeakerReadService $read,
        private readonly SdtgSpeakerWriteService $write,
        private readonly SdtgPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSdtg($admin);

        return view('sdtg.speakers.index', [
            'result' => $this->read->list(
                (int) $request->query('year', date('Y')),
                max(1, (int) $request->query('page', 1)),
            ),
            'canManage' => $this->policy->manageSdtg($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageSdtg($this->admin());

        return view('sdtg.speakers.create');
    }

    public function store(SaveSdtgSpeakerRequest $request): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());

        try {
            $this->write->save($request->validated());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()->route('sdtg.speakers.index')->with('status', 'Speaker saved.');
    }

    public function show(SdtgSpeaker $speaker): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSdtg($admin);
        $row = $this->read->get((int) $speaker->id);
        abort_if($row === null, 404);

        return view('sdtg.speakers.show', [
            'speaker' => $row,
            'canManage' => $this->policy->manageSdtg($admin),
        ]);
    }

    public function edit(SdtgSpeaker $speaker): View
    {
        $this->policy->requireManageSdtg($this->admin());
        $row = $this->read->get((int) $speaker->id);
        abort_if($row === null, 404);

        return view('sdtg.speakers.edit', ['speaker' => $row]);
    }

    public function update(SaveSdtgSpeakerRequest $request, SdtgSpeaker $speaker): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());

        $data = $request->validated();
        $data['id'] = (int) $speaker->id;

        try {
            $this->write->save($data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()->route('sdtg.speakers.show', $speaker)->with('status', 'Speaker updated.');
    }
}
