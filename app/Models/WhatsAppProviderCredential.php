<?php

namespace App\Models;

use Database\Factories\WhatsAppProviderCredentialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Credentials of a WhatsApp provider, stored encrypted.
 *
 * The webhook secret is part of the status webhook URL given to the provider,
 * so only the provider can report deliveries and reads.
 *
 * @property int $id
 * @property string $provider
 * @property array<string, string> $credentials
 * @property string $webhook_secret
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['provider', 'credentials', 'webhook_secret'])]
#[Hidden(['credentials', 'webhook_secret'])]
class WhatsAppProviderCredential extends Model
{
    /** @use HasFactory<WhatsAppProviderCredentialFactory> */
    use HasFactory;

    /**
     * Generate the webhook secret when the credentials are first stored.
     */
    protected static function booted(): void
    {
        static::creating(function (WhatsAppProviderCredential $credential): void {
            $credential->webhook_secret ??= Str::random(48);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
        ];
    }
}
