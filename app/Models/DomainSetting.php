<?php

namespace App\Models;

use Database\Factories\DomainSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $old_domain
 * @property string $new_domain
 * @property string $new_domain_url
 * @property int $redirect_seconds
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['old_domain', 'new_domain', 'new_domain_url', 'redirect_seconds'])]
class DomainSetting extends Model
{
    /** @use HasFactory<DomainSettingFactory> */
    use HasFactory;

    /**
     * Get the domain settings currently in effect.
     */
    public static function current(): self
    {
        return static::query()->oldest('id')->firstOrFail();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'redirect_seconds' => 'integer',
        ];
    }
}
