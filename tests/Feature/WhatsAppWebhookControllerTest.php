<?php

use App\Models\WhatsAppCampaignRecipient;
use App\Models\WhatsAppProviderCredential;

/**
 * @param  list<string>  $messageIds
 * @return array<string, mixed>
 */
function zApiStatusPayload(string $status, array $messageIds): array
{
    return [
        'instanceId' => 'INSTANCE123',
        'status' => $status,
        'ids' => $messageIds,
        'momment' => 1632234645000,
        'phoneDevice' => 0,
        'phone' => '5511999991234',
        'type' => 'MessageStatusCallback',
        'isGroup' => false,
    ];
}

test('a delivery confirmation marks the message as delivered', function () {
    $this->freezeTime();
    $credential = WhatsAppProviderCredential::factory()->create();
    $recipient = WhatsAppCampaignRecipient::factory()->sent()->create(['provider_message_id' => 'MSG1']);

    $this->postJson(route('whatsapp.webhooks.status', ['zapi', $credential->webhook_secret]), zApiStatusPayload('RECEIVED', ['MSG1']))
        ->assertOk();

    expect($recipient->refresh())
        ->delivered_at->toDateTimeString()->toBe(now()->toDateTimeString())
        ->read_at->toBeNull();
});

test('a read confirmation marks the message as delivered and read', function (string $status) {
    $this->freezeTime();
    $credential = WhatsAppProviderCredential::factory()->create();
    $recipient = WhatsAppCampaignRecipient::factory()->sent()->create(['provider_message_id' => 'MSG1']);

    $this->postJson(route('whatsapp.webhooks.status', ['zapi', $credential->webhook_secret]), zApiStatusPayload($status, ['MSG1']))
        ->assertOk();

    expect($recipient->refresh())
        ->delivered_at->not->toBeNull()
        ->read_at->toDateTimeString()->toBe(now()->toDateTimeString());
})->with(['READ', 'PLAYED']);

test('a repeated read confirmation keeps the first read time', function () {
    $credential = WhatsAppProviderCredential::factory()->create();
    $firstReadAt = now()->subHour()->startOfSecond();
    $recipient = WhatsAppCampaignRecipient::factory()->sent()->create([
        'provider_message_id' => 'MSG1',
        'delivered_at' => $firstReadAt,
        'read_at' => $firstReadAt,
    ]);

    $this->postJson(route('whatsapp.webhooks.status', ['zapi', $credential->webhook_secret]), zApiStatusPayload('READ', ['MSG1']))
        ->assertOk();

    expect($recipient->refresh()->read_at->toDateTimeString())->toBe($firstReadAt->toDateTimeString());
});

test('other webhook events are ignored', function () {
    $credential = WhatsAppProviderCredential::factory()->create();
    $recipient = WhatsAppCampaignRecipient::factory()->sent()->create(['provider_message_id' => 'MSG1']);

    $this->postJson(route('whatsapp.webhooks.status', ['zapi', $credential->webhook_secret]), zApiStatusPayload('SENT', ['MSG1']))
        ->assertOk();
    $this->postJson(route('whatsapp.webhooks.status', ['zapi', $credential->webhook_secret]), ['type' => 'ReceivedCallback', 'ids' => ['MSG1']])
        ->assertOk();

    expect($recipient->refresh())
        ->delivered_at->toBeNull()
        ->read_at->toBeNull();
});

test('a webhook with the wrong secret is rejected', function () {
    WhatsAppProviderCredential::factory()->create();
    $recipient = WhatsAppCampaignRecipient::factory()->sent()->create(['provider_message_id' => 'MSG1']);

    $this->postJson(route('whatsapp.webhooks.status', ['zapi', 'wrong-secret']), zApiStatusPayload('READ', ['MSG1']))
        ->assertNotFound();

    expect($recipient->refresh()->read_at)->toBeNull();
});
