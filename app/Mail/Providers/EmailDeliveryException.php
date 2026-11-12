<?php

namespace App\Mail\Providers;

use RuntimeException;

/**
 * Thrown when an e-mail provider rejects the credentials or the message.
 */
class EmailDeliveryException extends RuntimeException
{
    //
}
