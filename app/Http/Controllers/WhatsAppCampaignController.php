<?php

namespace App\Http\Controllers;

use App\Concerns\PresentsProviderCredentials;
use App\Http\Requests\WhatsAppCampaignStoreRequest;
use App\Jobs\SendWhatsAppMessage;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use App\Models\WhatsAppProviderCredential;
use App\WhatsApp\DomainChangeWhatsAppMessage;
use App\WhatsApp\Providers\WhatsAppProviderFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppCampaignController extends Controller
{
    use PresentsProviderCredentials;

    /**
     * Show the WhatsApp campaign page.
     */
    public function index(
        WhatsAppProviderFactory $whatsAppProviderFactory,
        DomainChangeWhatsAppMessage $domainChangeWhatsAppMessage,
    ): Response {
        $storedCredentials = WhatsAppProviderCredential::query()->get()->keyBy('provider');

        $providers = array_map(function (string $providerKey) use ($whatsAppProviderFactory, $storedCredentials): array {
            $credential = $storedCredentials->get($providerKey);

            return [
                ...$this->presentProvider(
                    $providerKey,
                    $whatsAppProviderFactory->label($providerKey),
                    $whatsAppProviderFactory->credentialFields($providerKey),
                    $credential?->credentials ?? [],
                ),
                'webhookUrl' => $credential === null ? null : route('whatsapp.webhooks.status', [
                    'provider' => $providerKey,
                    'secret' => $credential->webhook_secret,
                ]),
            ];
        }, $whatsAppProviderFactory->keys());

        return Inertia::render('WhatsApp', [
            'providers' => $providers,
            'maxRecipients' => WhatsAppCampaignStoreRequest::MAX_RECIPIENTS,
            'defaultMessage' => $domainChangeWhatsAppMessage->defaultText(),
            'secondsBetweenMessages' => config('whatsapp.seconds_between_messages'),
            'stats' => $this->stats(),
            'recentCampaigns' => WhatsAppCampaign::query()
                ->withCount([
                    'recipients as sent_count' => fn ($query) => $query->where('status', WhatsAppCampaignRecipient::STATUS_SENT),
                    'recipients as failed_count' => fn ($query) => $query->where('status', WhatsAppCampaignRecipient::STATUS_FAILED),
                    'recipients as read_count' => fn ($query) => $query->whereNotNull('read_at'),
                ])
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (WhatsAppCampaign $campaign) => [
                    'id' => $campaign->id,
                    'message' => $campaign->message,
                    'provider' => $whatsAppProviderFactory->exists($campaign->provider)
                        ? $whatsAppProviderFactory->label($campaign->provider)
                        : $campaign->provider,
                    'recipients_count' => $campaign->recipients_count,
                    'sent_count' => $campaign->sent_count,
                    'failed_count' => $campaign->failed_count,
                    'read_count' => $campaign->read_count,
                    'created_at' => $campaign->created_at?->toIso8601String(),
                ]),
        ]);
    }

    /**
     * Create the campaign and queue one message per number, spread out over time.
     */
    public function store(WhatsAppCampaignStoreRequest $request): RedirectResponse
    {
        $phoneNumbers = $request->phoneNumbers();

        $campaign = DB::transaction(function () use ($request, $phoneNumbers): WhatsAppCampaign {
            $campaign = WhatsAppCampaign::create([
                'user_id' => $request->user()?->id,
                'provider' => $request->validated('provider'),
                'message' => $request->validated('message'),
                'recipients_count' => count($phoneNumbers),
            ]);

            $now = now();

            WhatsAppCampaignRecipient::insert(array_map(fn (string $phoneNumber) => [
                'whats_app_campaign_id' => $campaign->id,
                'phone' => $phoneNumber,
                'status' => WhatsAppCampaignRecipient::STATUS_QUEUED,
                'created_at' => $now,
                'updated_at' => $now,
            ], $phoneNumbers));

            return $campaign;
        });

        $secondsBetweenMessages = (int) config('whatsapp.seconds_between_messages');
        $position = 0;

        $campaign->recipients()->lazyById()->each(function (WhatsAppCampaignRecipient $recipient) use (&$position, $secondsBetweenMessages): void {
            SendWhatsAppMessage::dispatch($recipient)->delay(now()->addSeconds($position * $secondsBetweenMessages));
            $position++;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => sprintf('Disparo iniciado para %d número(s).', count($phoneNumbers)),
        ]);

        return to_route('whatsapp.index');
    }

    /**
     * Get the delivery stats of all campaigns.
     *
     * @return array{sent: int, queued: int, failed: int, delivered: int, read: int}
     */
    private function stats(): array
    {
        $countsByStatus = WhatsAppCampaignRecipient::query()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'sent' => (int) ($countsByStatus[WhatsAppCampaignRecipient::STATUS_SENT] ?? 0),
            'queued' => (int) ($countsByStatus[WhatsAppCampaignRecipient::STATUS_QUEUED] ?? 0),
            'failed' => (int) ($countsByStatus[WhatsAppCampaignRecipient::STATUS_FAILED] ?? 0),
            'delivered' => WhatsAppCampaignRecipient::query()->whereNotNull('delivered_at')->count(),
            'read' => WhatsAppCampaignRecipient::query()->whereNotNull('read_at')->count(),
        ];
    }
}
