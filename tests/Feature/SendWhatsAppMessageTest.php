<?php

use App\Jobs\SendWhatsAppMessage;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use App\Models\WhatsAppProviderCredential;
use App\WhatsApp\Providers\WhatsAppDeliveryException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const ZAPI_SEND_TEXT_URL = 'https://api.z-api.io/instances/INSTANCE123/token/TOKEN456/send-text';

test('the message is sent through Z-API and the recipient is marked as sent', function () {
    Http::preventStrayRequests();
    Http::fake([ZAPI_SEND_TEXT_URL => Http::response([
        'zaapId' => 'ZAAP1',
        'messageId' => '3EB0ABC123',
        'id' => '3EB0ABC123',
    ])]);
    WhatsAppProviderCredential::factory()->create();
    $recipient = WhatsAppCampaignRecipient::factory()
        ->for(WhatsAppCampaign::factory()->state(['message' => '*Mudamos* para bet368.games']), 'campaign')
        ->create(['phone' => '5511999991234']);

    SendWhatsAppMessage::dispatchSync($recipient);

    Http::assertSent(fn (Request $request) => $request->url() === ZAPI_SEND_TEXT_URL
        && $request->method() === 'POST'
        && $request->hasHeader('Client-Token', 'CLIENT789')
        && $request['phone'] === '5511999991234'
        && $request['message'] === '*Mudamos* para bet368.games');

    expect($recipient->refresh())
        ->status->toBe(WhatsAppCampaignRecipient::STATUS_SENT)
        ->provider_message_id->toBe('3EB0ABC123')
        ->sent_at->not->toBeNull();
});

test('the recipient is marked as failed when Z-API rejects the message', function () {
    Http::preventStrayRequests();
    Http::fake([ZAPI_SEND_TEXT_URL => Http::response(['error' => 'phone not exists'], 400)]);
    WhatsAppProviderCredential::factory()->create();
    $recipient = WhatsAppCampaignRecipient::factory()->create();

    expect(fn () => SendWhatsAppMessage::dispatchSync($recipient))->toThrow(WhatsAppDeliveryException::class);

    expect($recipient->refresh())
        ->status->toBe(WhatsAppCampaignRecipient::STATUS_FAILED)
        ->error->toBe('Z-API (400): phone not exists');
});

test('the recipient is marked as failed when the provider credentials are missing', function () {
    Http::preventStrayRequests();
    $recipient = WhatsAppCampaignRecipient::factory()->create();

    SendWhatsAppMessage::dispatchSync($recipient);

    expect($recipient->refresh())
        ->status->toBe(WhatsAppCampaignRecipient::STATUS_FAILED)
        ->error->toBe('As credenciais do serviço de WhatsApp não estão configuradas.');

    Http::assertNothingSent();
});

test('a recipient that was already sent is not sent again', function () {
    Http::preventStrayRequests();
    WhatsAppProviderCredential::factory()->create();
    $recipient = WhatsAppCampaignRecipient::factory()->sent()->create();

    SendWhatsAppMessage::dispatchSync($recipient);

    Http::assertNothingSent();
});
