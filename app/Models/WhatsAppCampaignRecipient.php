<?php

namespace App\Models;

use Database\Factories\WhatsAppCampaignRecipientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $whats_app_campaign_id
 * @property string $phone
 * @property string $status
 * @property string|null $provider_message_id
 * @property string|null $error
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'whats_app_campaign_id',
    'phone',
    'status',
    'provider_message_id',
    'error',
    'sent_at',
    'delivered_at',
    'read_at',
])]
class WhatsAppCampaignRecipient extends Model
{
    /** @use HasFactory<WhatsAppCampaignRecipientFactory> */
    use HasFactory;

    public const string STATUS_QUEUED = 'queued';

    public const string STATUS_SENT = 'sent';

    public const string STATUS_FAILED = 'failed';

    /**
     * Get the campaign the recipient belongs to.
     *
     * @return BelongsTo<WhatsAppCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WhatsAppCampaign::class, 'whats_app_campaign_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }
}
