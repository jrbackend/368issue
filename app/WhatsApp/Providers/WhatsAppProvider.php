<?php

namespace App\WhatsApp\Providers;

/**
 * Strategy for delivering WhatsApp messages through a specific WhatsApp API.
 *
 * Each implementation describes the credentials it needs, so the dashboard
 * can render and validate the credentials form without knowing the service.
 */
interface WhatsAppProvider
{
    /**
     * The unique key used to store and select the provider (e.g. "zapi").
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
     * Check that the credentials work and the WhatsApp number is connected.
     *
     * @throws WhatsAppDeliveryException
     */
    public function verifyCredentials(): void;

    /**
     * Send the message and return the WhatsApp message id, when available.
     *
     * @throws WhatsAppDeliveryException
     */
    public function send(OutgoingWhatsAppMessage $message): ?string;

    /**
     * Read a delivery/read status webhook sent by the provider, or null when it isn't one.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function parseStatusWebhook(array $payload): ?WhatsAppStatusUpdate;
}
