<?php

namespace App\Models;

use Database\Factories\EmailProviderCredentialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Credentials of an e-mail provider, stored encrypted.
 *
 * @property int $id
 * @property string $provider
 * @property array<string, string> $credentials
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['provider', 'credentials'])]
#[Hidden(['credentials'])]
class EmailProviderCredential extends Model
{
    /** @use HasFactory<EmailProviderCredentialFactory> */
    use HasFactory;

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
