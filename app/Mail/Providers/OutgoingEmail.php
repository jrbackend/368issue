<?php

namespace App\Mail\Providers;

/**
 * An e-mail ready to be handed to an e-mail provider.
 */
final readonly class OutgoingEmail
{
    public function __construct(
        public string $to,
        public string $subject,
        public string $html,
    ) {}
}
