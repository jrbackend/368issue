<?php

namespace App\Models;

use Database\Factories\WhatsAppCampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $provider
 * @property string $message
 * @property int $recipients_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'provider', 'message', 'recipients_count'])]
class WhatsAppCampaign extends Model
{
    /** @use HasFactory<WhatsAppCampaignFactory> */
    use HasFactory;

    /**
     * Get the user that started the campaign.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the campaign recipients.
     *
     * @return HasMany<WhatsAppCampaignRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsAppCampaignRecipient::class);
    }
}
