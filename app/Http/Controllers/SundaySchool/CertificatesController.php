<?php

namespace App\Http\Controllers\SundaySchool;

use App\Models\Admin;
use App\Models\SundaySchoolAward;
use App\Models\SundaySchoolCertificate;
use App\Policies\SundaySchoolPolicy;
use App\Services\SundaySchool\CertificateReadService;
use App\Services\SundaySchool\CertificateWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CertificatesController
{
    public function __construct(
        private readonly CertificateReadService $read,
        private readonly CertificateWriteService $write,
        private readonly SundaySchoolPolicy $policy,
    ) {}

    public function index(): View
    {
        $this->policy->requireManageClasses($this->admin());

        return view('sunday-school.certificates.index', [
            'items' => $this->read->listCertificates(),
        ]);
    }

    public function generateFromAward(SundaySchoolAward $award): RedirectResponse
    {
        $this->policy->requireManageClasses($this->admin());

        try {
            $this->write->generateFromAward((int) $award->id, (int) $this->admin()->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['certificate' => $e->getMessage()]);
        }

        return back()->with('status', 'Certificate generated.');
    }

    public function regenerate(SundaySchoolCertificate $certificate): RedirectResponse
    {
        $this->policy->requireManageClasses($this->admin());

        try {
            $this->write->regenerateCertificate((int) $certificate->id, (int) $this->admin()->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['certificate' => $e->getMessage()]);
        }

        return back()->with('status', 'Certificate regenerated.');
    }

    public function destroy(SundaySchoolCertificate $certificate): RedirectResponse
    {
        $this->policy->requireManageClasses($this->admin());

        try {
            $this->write->deleteCertificate((int) $certificate->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['certificate' => $e->getMessage()]);
        }

        return redirect()->route('ss.certificates.index')->with('status', 'Certificate deleted.');
    }

    public function download(SundaySchoolCertificate $certificate): BinaryFileResponse|RedirectResponse
    {
        $this->policy->requireManageClasses($this->admin());

        $file = $this->read->getCertificateFile((int) $certificate->id);
        if ($file === null) {
            return back()->withErrors(['certificate' => 'Certificate file not found on disk. Try regenerating it.']);
        }

        return response()->download($file['path'], $file['number'].'.pdf');
    }

    public function preview(SundaySchoolCertificate $certificate): BinaryFileResponse|RedirectResponse
    {
        $this->policy->requireManageClasses($this->admin());

        $file = $this->read->getCertificateFile((int) $certificate->id);
        if ($file === null) {
            return back()->withErrors(['certificate' => 'Certificate file not found on disk. Try regenerating it.']);
        }

        return response()->file($file['path'], [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$file['number'].'.pdf"',
        ]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
