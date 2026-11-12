<?php

namespace App\Jobs;

use App\Models\WhatsAppCampaignRecipient;
use App\Models\WhatsAppProviderCredential;
use App\WhatsApp\Providers\OutgoingWhatsAppMessage;
use App\WhatsApp\Providers\WhatsAppDeliveryException;
use App\WhatsApp\Providers\WhatsAppProviderFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The seconds to wait before retrying the job.
     *
     * @var list<int>
     */
    public array $backoff = [30, 120];

    /**
     * Delete the job if the recipient no longer exists.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(public WhatsAppCampaignRecipient $recipient) {}

    /**
     * Send the campaign message to the recipient through the campaign's WhatsApp provider.
     *
     * @throws WhatsAppDeliveryException
     */
    public function handle(WhatsAppProviderFactory $whatsAppProviderFactory): void
    {
        if ($this->recipient->status !== WhatsAppCampaignRecipient::STATUS_QUEUED) {
            return;
        }

        $campaign = $this->recipient->campaign;

        $credential = WhatsAppProviderCredential::query()->where('provider', $campaign->provider)->first();

        if ($credential === null) {
            $this->fail(new WhatsAppDeliveryException('As credenciais do serviço de WhatsApp não estão configuradas.'));

            return;
        }

        $messageId = $whatsAppProviderFactory
            ->make($campaign->provider, $credential->credentials)
            ->send(new OutgoingWhatsAppMessage(
                phone: $this->recipient->phone,
                text: $campaign->message,
            ));

        $this->recipient->update([
            'status' => WhatsAppCampaignRecipient::STATUS_SENT,
            'provider_message_id' => $messageId,
            'sent_at' => now(),
            'error' => null,
        ]);
    }

    /**
     * Mark the recipient as failed once all attempts are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        $this->recipient->update([
            'status' => WhatsAppCampaignRecipient::STATUS_FAILED,
            'error' => Str::limit($exception?->getMessage() ?: 'Erro desconhecido.', 250),
        ]);
    }
}
