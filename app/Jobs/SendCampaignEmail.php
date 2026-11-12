<?php

namespace App\Jobs;

use App\Mail\DomainChangeEmail;
use App\Mail\Providers\EmailDeliveryException;
use App\Mail\Providers\EmailProviderFactory;
use App\Mail\Providers\OutgoingEmail;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailProviderCredential;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class SendCampaignEmail implements ShouldQueue
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
    public array $backoff = [10, 60];

    /**
     * Delete the job if the recipient no longer exists.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(public EmailCampaignRecipient $recipient) {}

    /**
     * Send the domain change e-mail to the recipient through the campaign's e-mail provider.
     *
     * @throws EmailDeliveryException
     */
    public function handle(EmailProviderFactory $emailProviderFactory, DomainChangeEmail $domainChangeEmail): void
    {
        if ($this->recipient->status !== EmailCampaignRecipient::STATUS_QUEUED) {
            return;
        }

        $campaign = $this->recipient->campaign;

        $credential = EmailProviderCredential::query()->where('provider', $campaign->provider)->first();

        if ($credential === null) {
            $this->fail(new EmailDeliveryException('As credenciais do serviço de e-mail não estão configuradas.'));

            return;
        }

        $messageId = $emailProviderFactory
            ->make($campaign->provider, $credential->credentials)
            ->send(new OutgoingEmail(
                to: $this->recipient->email,
                subject: $campaign->subject,
                html: $domainChangeEmail->render(route('email.open', $this->recipient->tracking_token)),
            ));

        $this->recipient->update([
            'status' => EmailCampaignRecipient::STATUS_SENT,
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
            'status' => EmailCampaignRecipient::STATUS_FAILED,
            'error' => Str::limit($exception?->getMessage() ?: 'Erro desconhecido.', 250),
        ]);
    }
}
