<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppCampaignRecipient;
use App\Models\WhatsAppProviderCredential;
use App\WhatsApp\Providers\WhatsAppProviderFactory;
use App\WhatsApp\Providers\WhatsAppStatusUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    /**
     * Record the delivery and read confirmations reported by the WhatsApp provider.
     */
    public function __invoke(
        Request $request,
        WhatsAppProviderFactory $whatsAppProviderFactory,
        string $provider,
        string $secret,
    ): JsonResponse {
        $credential = WhatsAppProviderCredential::query()->where('provider', $provider)->first();

        abort_if($credential === null || ! hash_equals($credential->webhook_secret, $secret), 404);

        $statusUpdate = $whatsAppProviderFactory->parseStatusWebhook($provider, $request->all());

        if ($statusUpdate !== null && $statusUpdate->messageIds !== []) {
            $recipients = WhatsAppCampaignRecipient::query()->whereIn('provider_message_id', $statusUpdate->messageIds);

            (clone $recipients)->whereNull('delivered_at')->update(['delivered_at' => now()]);

            if ($statusUpdate->status === WhatsAppStatusUpdate::READ) {
                (clone $recipients)->whereNull('read_at')->update(['read_at' => now()]);
            }
        }

        return response()->json(['received' => true]);
    }
}
