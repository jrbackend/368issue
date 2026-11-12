<?php

namespace App\Models;

use Database\Factories\TrafficRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property bool $is_enabled
 * @property list<string> $allowed_devices
 * @property list<string> $allowed_languages
 * @property list<string> $allowed_countries
 * @property bool $allow_unknown_country
 * @property string $blocked_action
 * @property string|null $blocked_redirect_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'is_enabled',
    'allowed_devices',
    'allowed_languages',
    'allowed_countries',
    'allow_unknown_country',
    'blocked_action',
    'blocked_redirect_url',
])]
class TrafficRule extends Model
{
    /** @use HasFactory<TrafficRuleFactory> */
    use HasFactory;

    /**
     * Blocked visitors see the "access denied" page (HTTP 403).
     */
    public const string ACTION_BLOCK = 'block';

    /**
     * Blocked visitors are redirected to the configured URL.
     */
    public const string ACTION_REDIRECT = 'redirect';

    /**
     * Reasons a visitor can be blocked.
     */
    public const string REASON_DEVICE = 'device';

    public const string REASON_LANGUAGE = 'language';

    public const string REASON_COUNTRY = 'country';

    public const string REASON_UNKNOWN_COUNTRY = 'unknown_country';

    /**
     * Get the traffic rules currently in effect.
     */
    public static function current(): self
    {
        return static::query()->oldest('id')->firstOrFail();
    }

    /**
     * Determine if the rules restrict visitors by country.
     */
    public function restrictsCountries(): bool
    {
        return $this->is_enabled && $this->allowed_countries !== [];
    }

    /**
     * Get the reason a visitor with the given profile is blocked, or null when they may see the home page.
     *
     * An empty list means "no restriction" for that criterion.
     */
    public function blockedReason(string $device, ?string $language, ?string $country): ?string
    {
        if (! $this->is_enabled) {
            return null;
        }

        if ($this->allowed_devices !== [] && ! in_array($device, $this->allowed_devices, true)) {
            return self::REASON_DEVICE;
        }

        if ($this->allowed_languages !== [] && ! in_array($language, $this->allowed_languages, true)) {
            return self::REASON_LANGUAGE;
        }

        if ($this->allowed_countries === []) {
            return null;
        }

        if ($country === null) {
            return $this->allow_unknown_country ? null : self::REASON_UNKNOWN_COUNTRY;
        }

        return in_array($country, $this->allowed_countries, true) ? null : self::REASON_COUNTRY;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'allowed_devices' => 'array',
            'allowed_languages' => 'array',
            'allowed_countries' => 'array',
            'allow_unknown_country' => 'boolean',
        ];
    }
}
