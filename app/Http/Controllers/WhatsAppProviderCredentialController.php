<?php

namespace App\Http\Controllers;

use App\Http\Requests\WhatsAppProviderCredentialUpdateRequest;
use App\Models\WhatsAppProviderCredential;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class WhatsAppProviderCredentialController extends Controller
{
    /**
     * Save the (already verified) credentials of a WhatsApp provider.
     */
    public function update(WhatsAppProviderCredentialUpdateRequest $request): RedirectResponse
    {
        WhatsAppProviderCredential::updateOrCreate(
            ['provider' => $request->providerKey()],
            ['credentials' => $request->credentials()],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Credenciais salvas e verificadas.']);

        return to_route('whatsapp.index');
    }
}
