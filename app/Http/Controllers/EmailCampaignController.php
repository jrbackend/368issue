<?php

namespace App\Http\Controllers;

use App\Concerns\PresentsProviderCredentials;
use App\Http\Requests\EmailCampaignStoreRequest;
use App\Jobs\SendCampaignEmail;
use App\Mail\DomainChangeEmail;
use App\Mail\Providers\EmailProviderFactory;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailProviderCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmailCampaignController extends Controller
{
    use PresentsProviderCredentials;

    /**
     * Show the e-mail campaign page.
     */
    public function index(EmailProviderFactory $emailProviderFactory, DomainChangeEmail $domainChangeEmail): Response
    {
        $storedCredentials = EmailProviderCredential::query()->get()->keyBy('provider');

        $providers = array_map(fn (string $providerKey) => $this->presentProvider(
            $providerKey,
            $emailProviderFactory->label($providerKey),
            $emailProviderFactory->credentialFields($providerKey),
            $storedCredentials->get($providerKey)?->credentials ?? [],
        ), $emailProviderFactory->keys());

        return Inertia::render('Email', [
            'providers' => $providers,
            'maxRecipients' => EmailCampaignStoreRequest::MAX_RECIPIENTS,
            'defaultSubject' => $domainChangeEmail->defaultSubject(),
            'previewHtml' => $domainChangeEmail->render(),
            'stats' => $this->stats(),
            'recentCampaigns' => EmailCampaign::query()
                ->withCount([
                    'recipients as sent_count' => fn ($query) => $query->where('status', EmailCampaignRecipient::STATUS_SENT),
                    'recipients as failed_count' => fn ($query) => $query->where('status', EmailCampaignRecipient::STATUS_FAILED),
                    'recipients as opened_count' => fn ($query) => $query->whereNotNull('opened_at'),
                ])
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (EmailCampaign $campaign) => [
                    'id' => $campaign->id,
                    'subject' => $campaign->subject,
                    'provider' => $emailProviderFactory->exists($campaign->provider)
                        ? $emailProviderFactory->label($campaign->provider)
                        : $campaign->provider,
                    'recipients_count' => $campaign->recipients_count,
                    'sent_count' => $campaign->sent_count,
                    'failed_count' => $campaign->failed_count,
                    'opened_count' => $campaign->opened_count,
                    'created_at' => $campaign->created_at?->toIso8601String(),
                ]),
        ]);
    }

    /**
     * Create the campaign and queue one e-mail per recipient.
     */
    public function store(EmailCampaignStoreRequest $request): RedirectResponse
    {
        $addresses = $request->addresses();

        $campaign = DB::transaction(function () use ($request, $addresses): EmailCampaign {
            $campaign = EmailCampaign::create([
                'user_id' => $request->user()?->id,
                'provider' => $request->validated('provider'),
                'subject' => $request->validated('subject'),
                'recipients_count' => count($addresses),
            ]);

            $now = now();

            foreach (array_chunk($addresses, 500) as $chunk) {
                EmailCampaignRecipient::insert(array_map(fn (string $address) => [
                    'email_campaign_id' => $campaign->id,
                    'email' => $address,
                    'status' => EmailCampaignRecipient::STATUS_QUEUED,
                    'tracking_token' => (string) Str::uuid(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk));
            }

            return $campaign;
        });

        $campaign->recipients()->lazyById()->each(
            fn (EmailCampaignRecipient $recipient) => SendCampaignEmail::dispatch($recipient),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => sprintf('Disparo iniciado para %s e-mail(s).', number_format(count($addresses), thousands_separator: '.')),
        ]);

        return to_route('email.index');
    }

    /**
     * Get the delivery stats of all campaigns.
     *
     * @return array{sent: int, queued: int, failed: int, opened: int}
     */
    private function stats(): array
    {
        $countsByStatus = EmailCampaignRecipient::query()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'sent' => (int) ($countsByStatus[EmailCampaignRecipient::STATUS_SENT] ?? 0),
            'queued' => (int) ($countsByStatus[EmailCampaignRecipient::STATUS_QUEUED] ?? 0),
            'failed' => (int) ($countsByStatus[EmailCampaignRecipient::STATUS_FAILED] ?? 0),
            'opened' => EmailCampaignRecipient::query()->whereNotNull('opened_at')->count(),
        ];
    }
}
