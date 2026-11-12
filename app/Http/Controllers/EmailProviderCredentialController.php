<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailProviderCredentialUpdateRequest;
use App\Models\EmailProviderCredential;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class EmailProviderCredentialController extends Controller
{
    /**
     * Save the (already verified) credentials of an e-mail provider.
     */
    public function update(EmailProviderCredentialUpdateRequest $request): RedirectResponse
    {
        EmailProviderCredential::updateOrCreate(
            ['provider' => $request->providerKey()],
            ['credentials' => $request->credentials()],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Credenciais salvas e verificadas.']);

        return to_route('email.index');
    }
}
