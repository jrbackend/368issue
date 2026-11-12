<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Traffic Control Options
    |--------------------------------------------------------------------------
    |
    | These lists define which devices, languages and countries may be chosen
    | on the traffic control page of the dashboard. The rules themselves are
    | stored in the database and decide who is allowed to see the home page.
    |
    */

    'devices' => ['desktop', 'mobile', 'tablet'],

    /*
    | ISO 639-1 codes. A visitor matches by the primary language of their
    | browser, so "pt" covers both "pt-BR" and "pt-PT".
    */

    'languages' => [
        'pt', 'en', 'es', 'fr', 'de', 'it', 'nl', 'ru', 'uk', 'pl', 'cs', 'ro',
        'hu', 'el', 'sv', 'da', 'nb', 'fi', 'tr', 'ar', 'he', 'fa', 'hi', 'bn',
        'zh', 'ja', 'ko', 'th', 'vi', 'id', 'ms', 'tl',
    ],

    /*
    | ISO 3166-1 alpha-2 codes.
    */

    'countries' => [
        'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
        'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS',
        'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN',
        'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE',
        'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF',
        'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM',
        'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM',
        'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC',
        'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK',
        'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA',
        'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG',
        'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW',
        'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS',
        'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO',
        'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI',
        'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW',
    ],

    /*
    |--------------------------------------------------------------------------
    | Country Lookup Cache
    |--------------------------------------------------------------------------
    |
    | How long (in minutes) the country of an IP address is cached, so the
    | geolocation service is not called on every visit. Failed lookups are
    | cached for a shorter time so a temporary outage recovers quickly.
    |
    */

    'country_cache_minutes' => (int) env('TRAFFIC_COUNTRY_CACHE_MINUTES', 1440),

    'failed_country_cache_minutes' => 10,

    /*
    |--------------------------------------------------------------------------
    | Traffic Log Retention
    |--------------------------------------------------------------------------
    |
    | Visits older than this many days are deleted by the daily
    | "model:prune" schedule, keeping the log (and stored IPs) small.
    |
    */

    'log_retention_days' => (int) env('TRAFFIC_LOG_RETENTION_DAYS', 30),

];
