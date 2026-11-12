<?php

namespace App\Http\Controllers;

use App\Http\Requests\DomainSettingUpdateRequest;
use App\Models\DomainSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DomainSettingController extends Controller
{
    /**
     * Show the dashboard to manage the domain transfer settings.
     */
    public function edit(): Response
    {
        $domainSetting = DomainSetting::current();

        return Inertia::render('Dashboard', [
            'domainSetting' => $domainSetting->only([
                'old_domain',
                'new_domain',
                'new_domain_url',
                'redirect_seconds',
            ]),
            'updatedAt' => $domainSetting->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Update the domain transfer settings.
     */
    public function update(DomainSettingUpdateRequest $request): RedirectResponse
    {
        DomainSetting::current()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configurações de domínio atualizadas.']);

        return to_route('dashboard');
    }
}
