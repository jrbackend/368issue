<?php

namespace App\WhatsApp\Providers;

/**
 * A delivery or read confirmation reported by a WhatsApp provider.
 */
final readonly class WhatsAppStatusUpdate
{
    public const string DELIVERED = 'delivered';

    public const string READ = 'read';

    /**
     * @param  list<string>  $messageIds
     * @param  self::DELIVERED|self::READ  $status
     */
    public function __construct(
        public array $messageIds,
        public string $status,
    ) {}
}
