<?php

namespace App\Http\Requests;

use App\Mail\Providers\EmailDeliveryException;
use App\Mail\Providers\EmailProviderFactory;
use App\Models\EmailProviderCredential;

class EmailProviderCredentialUpdateRequest extends ProviderCredentialUpdateRequest
{
    protected function credentialFields(): array
    {
        return app(EmailProviderFactory::class)->credentialFields($this->providerKey());
    }

    protected function storedCredentials(): array
    {
        return EmailProviderCredential::query()
            ->where('provider', $this->providerKey())
            ->first()
            ?->credentials ?? [];
    }

    protected function verificationError(array $credentials): ?string
    {
        try {
            app(EmailProviderFactory::class)->make($this->providerKey(), $credentials)->verifyCredentials();
        } catch (EmailDeliveryException $exception) {
            return $exception->getMessage();
        }

        return null;
    }
}
