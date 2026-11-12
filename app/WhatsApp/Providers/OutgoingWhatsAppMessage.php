<?php

namespace App\WhatsApp\Providers;

/**
 * A WhatsApp text message ready to be handed to a WhatsApp provider.
 */
final readonly class OutgoingWhatsAppMessage
{
    /**
     * @param  string  $phone  Digits only, including the country code (e.g. "5511999999999").
     */
    public function __construct(
        public string $phone,
        public string $text,
    ) {}
}
