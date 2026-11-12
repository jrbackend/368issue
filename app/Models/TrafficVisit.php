<?php

namespace App\Models;

use Database\Factories\TrafficVisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $ip
 * @property string|null $country_code
 * @property string|null $region_name
 * @property string|null $city_name
 * @property string $device
 * @property bool $is_bot
 * @property string|null $operating_system
 * @property string|null $browser
 * @property string|null $language
 * @property string|null $user_agent
 * @property bool $was_allowed
 * @property string|null $blocked_reason
 * @property Carbon $created_at
 */
#[Fillable([
    'ip',
    'country_code',
    'region_name',
    'city_name',
    'device',
    'is_bot',
    'operating_system',
    'browser',
    'language',
    'user_agent',
    'was_allowed',
    'blocked_reason',
])]
class TrafficVisit extends Model
{
    /** @use HasFactory<TrafficVisitFactory> */
    use HasFactory, MassPrunable;

    /**
     * Visits are never updated, so there is no "updated_at" column.
     */
    public const UPDATED_AT = null;

    /**
     * Get the prunable model query (visits older than the retention period).
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(config('traffic.log_retention_days')));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'was_allowed' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
