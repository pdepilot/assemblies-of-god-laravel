<?php

namespace App\Http\Controllers\RegistrationPortals;

use App\Models\Admin;
use App\Models\RegistrationPortal;
use App\Policies\RegistrationPortalPolicy;
use App\Services\RegistrationPortals\RegistrationPortalReadService;
use Illuminate\View\View;

final class PortalQrPageController
{
    public function __construct(
        private readonly RegistrationPortalReadService $read,
        private readonly RegistrationPortalPolicy $policy,
    ) {}

    public function __invoke(RegistrationPortal $registrationPortal): View
    {
        $this->policy->requireViewPortals($this->admin());

        $portal = $this->read->getPortal((int) $registrationPortal->id);
        abort_if($portal === null, 404);

        $registrationUrl = url('/register/'.$portal['slug']);
        $qrImg = url('/api/portal-qr.php?slug='.urlencode((string) $portal['slug']).'&size=12');
        $qrDownload = $qrImg.'&download=1';

        return view('registration-portals.qr', [
            'portal' => $portal,
            'registrationUrl' => $registrationUrl,
            'qrImg' => $qrImg,
            'qrDownload' => $qrDownload,
        ]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
