<?php

namespace App\Http\Controllers;

use App\Models\DomainSetting;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Show the domain transfer notice using the settings managed from the dashboard.
     */
    public function __invoke(): Response
    {
        $domainSetting = DomainSetting::current();

        return Inertia::render('Welcome', [
            'oldDomain' => $domainSetting->old_domain,
            'newDomain' => $domainSetting->new_domain,
            'newDomainUrl' => $domainSetting->new_domain_url,
            'redirectSeconds' => $domainSetting->redirect_seconds,
        ]);
    }
}
