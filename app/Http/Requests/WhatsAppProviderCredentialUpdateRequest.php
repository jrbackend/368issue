<?php

namespace App\Http\Requests;

use App\Models\WhatsAppProviderCredential;
use App\WhatsApp\Providers\WhatsAppDeliveryException;
use App\WhatsApp\Providers\WhatsAppProviderFactory;

class WhatsAppProviderCredentialUpdateRequest extends ProviderCredentialUpdateRequest
{
    protected function credentialFields(): array
    {
        return app(WhatsAppProviderFactory::class)->credentialFields($this->providerKey());
    }

    protected function storedCredentials(): array
    {
        return WhatsAppProviderCredential::query()
            ->where('provider', $this->providerKey())
            ->first()
            ?->credentials ?? [];
    }

    protected function verificationError(array $credentials): ?string
    {
        try {
            app(WhatsAppProviderFactory::class)->make($this->providerKey(), $credentials)->verifyCredentials();
        } catch (WhatsAppDeliveryException $exception) {
            return $exception->getMessage();
        }

        return null;
    }
}
