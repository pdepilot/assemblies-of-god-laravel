<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Admin;

trait ResolvesSdtgAdmin
{
    private function admin(): Admin
    {
        $admin = auth('sdtg')->user();
        if (! $admin instanceof Admin) {
            abort(401);
        }

        return $admin;
    }
}
