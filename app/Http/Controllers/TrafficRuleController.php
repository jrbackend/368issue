<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrafficRuleUpdateRequest;
use App\Models\TrafficRule;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TrafficRuleController extends Controller
{
    /**
     * Show the traffic control page.
     */
    public function edit(): Response
    {
        $trafficRule = TrafficRule::current();

        return Inertia::render('Traffic', [
            'trafficRule' => $trafficRule->only([
                'is_enabled',
                'allowed_devices',
                'allowed_languages',
                'allowed_countries',
                'allow_unknown_country',
                'blocked_action',
                'blocked_redirect_url',
            ]),
            'deviceOptions' => config('traffic.devices'),
            'languageOptions' => config('traffic.languages'),
            'countryOptions' => config('traffic.countries'),
        ]);
    }

    /**
     * Update the traffic rules.
     */
    public function update(TrafficRuleUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        TrafficRule::current()->update([
            ...$validated,
            'allowed_devices' => array_values(array_unique($validated['allowed_devices'] ?? [])),
            'allowed_languages' => array_values(array_unique($validated['allowed_languages'] ?? [])),
            'allowed_countries' => array_values(array_unique($validated['allowed_countries'] ?? [])),
            'blocked_redirect_url' => $validated['blocked_action'] === TrafficRule::ACTION_REDIRECT
                ? $validated['blocked_redirect_url']
                : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Controle de tráfego atualizado.']);

        return to_route('traffic.edit');
    }
}
