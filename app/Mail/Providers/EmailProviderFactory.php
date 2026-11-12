<?php

namespace App\Mail\Providers;

use InvalidArgumentException;

/**
 * Creates the e-mail provider strategy for a given provider key.
 *
 * To support a new e-mail service, implement EmailProvider and add it to PROVIDERS.
 */
class EmailProviderFactory
{
    /**
     * @var list<class-string<EmailProvider>>
     */
    private const array PROVIDERS = [
        TwilioSendGridProvider::class,
    ];

    /**
     * Create the provider for the given key using the given credentials.
     *
     * @param  array<string, string>  $credentials
     *
     * @throws InvalidArgumentException
     */
    public function make(string $providerKey, array $credentials): EmailProvider
    {
        $providerClass = $this->providerClass($providerKey);

        return new $providerClass($credentials);
    }

    /**
     * Determine if a provider with the given key exists.
     */
    public function exists(string $providerKey): bool
    {
        return in_array($providerKey, $this->keys(), true);
    }

    /**
     * Get the keys of all available providers.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return array_map(fn (string $providerClass) => $providerClass::key(), self::PROVIDERS);
    }

    /**
     * Get the label of the given provider.
     */
    public function label(string $providerKey): string
    {
        return $this->providerClass($providerKey)::label();
    }

    /**
     * Get the credential fields of the given provider.
     *
     * @return array<string, array{label: string, type: 'text'|'email'|'password', secret: bool, placeholder: string, help: string|null, rules: list<string>}>
     */
    public function credentialFields(string $providerKey): array
    {
        return $this->providerClass($providerKey)::credentialFields();
    }

    /**
     * @return class-string<EmailProvider>
     *
     * @throws InvalidArgumentException
     */
    private function providerClass(string $providerKey): string
    {
        foreach (self::PROVIDERS as $providerClass) {
            if ($providerClass::key() === $providerKey) {
                return $providerClass;
            }
        }

        throw new InvalidArgumentException("Unknown e-mail provider [{$providerKey}].");
    }
}
