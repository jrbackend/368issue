<?php

namespace App\Mail\Providers;

/**
 * Strategy for delivering e-mails through a specific e-mail service.
 *
 * Each implementation describes the credentials it needs, so the dashboard
 * can render and validate the credentials form without knowing the service.
 */
interface EmailProvider
{
    /**
     * The unique key used to store and select the provider (e.g. "twilio").
     */
    public static function key(): string;

    /**
     * The name shown on the dashboard.
     */
    public static function label(): string;

    /**
     * The credentials the provider needs.
     *
     * @return array<string, array{label: string, type: 'text'|'email'|'password', secret: bool, placeholder: string, help: string|null, rules: list<string>}>
     */
    public static function credentialFields(): array;

    /**
     * Check that the credentials can be used to send e-mails.
     *
     * @throws EmailDeliveryException
     */
    public function verifyCredentials(): void;

    /**
     * Send the e-mail and return the provider's message id, when available.
     *
     * @throws EmailDeliveryException
     */
    public function send(OutgoingEmail $email): ?string;
}
