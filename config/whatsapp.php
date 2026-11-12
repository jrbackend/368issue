<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seconds Between Messages
    |--------------------------------------------------------------------------
    |
    | WhatsApp blocks numbers that send many messages in a short time, so the
    | messages of a campaign are spread out: each one is queued this many
    | seconds after the previous one. 500 messages x 6s ≈ 50 minutes.
    |
    */

    'seconds_between_messages' => (int) env('WHATSAPP_SECONDS_BETWEEN_MESSAGES', 6),

    /*
    |--------------------------------------------------------------------------
    | Default Country Code
    |--------------------------------------------------------------------------
    |
    | Numbers typed without a country code (10 or 11 digits, e.g. "11 99999-9999")
    | are considered to be from this country.
    |
    */

    'default_country_code' => '55',

];
