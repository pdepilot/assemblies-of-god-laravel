<?php

namespace App\Http\Controllers\FinancialErp;

use App\Http\Requests\FinancialErp\SaveJournalRequest;
use App\Models\Admin;
use App\Models\ErpJournal;
use App\Policies\FinancialErpPolicy;
use App\Services\FinancialErp\AccountReadService;
use App\Services\FinancialErp\JournalReadService;
use App\Services\FinancialErp\JournalWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class JournalsController
{
    public function __construct(
        private readonly JournalReadService $read,
        private readonly JournalWriteService $write,
        private readonly AccountReadService $accounts,
        private readonly FinancialErpPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewErp($admin);
        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->read->listJournals($page, 25, $status !== '' ? $status : null);

        return view('financial-erp.journals.index', [
            'items' => $result['items'],
            'status' => $status,
            'page' => $result['page'],
            'totalPages' => $result['pages'],
            'canManage' => $this->policy->manageErp($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageErp($this->admin());

        return view('financial-erp.journals.create', [
            'accounts' => $this->accounts->listAccounts(),
        ]);
    }

    public function store(SaveJournalRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageErp($admin);
        $data = $request->validated();

        try {
            $journal = $this->write->save(
                $data['header'],
                $data['lines'],
                (int) $admin->id,
                ! empty($data['post']),
                (string) $admin->role,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['memo' => $e->getMessage()]);
        }

        return redirect()->route('financial-erp.journals.show', $journal['id'])->with('status', 'Journal saved.');
    }

    public function show(ErpJournal $journal): View
    {
        $admin = $this->admin();
        $this->policy->requireViewErp($admin);
        $data = $this->read->getJournal((int) $journal->id);
        abort_if($data === null, 404);

        return view('financial-erp.journals.show', [
            'journal' => $data,
            'canManage' => $this->policy->manageErp($admin),
        ]);
    }

    public function post(ErpJournal $journal): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageErp($admin);

        try {
            $this->write->post((int) $journal->id, (int) $admin->id, (string) $admin->role);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['memo' => $e->getMessage()]);
        }

        return redirect()->route('financial-erp.journals.show', $journal)->with('status', 'Journal posted.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
