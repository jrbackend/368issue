<?php

use App\Models\User;
use App\Models\WhatsAppProviderCredential;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

const ZAPI_STATUS_URL = 'https://api.z-api.io/instances/INSTANCE123/token/TOKEN456/status';

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function zApiCredentialsPayload(array $overrides = []): array
{
    return [
        'instance_id' => 'INSTANCE123',
        'instance_token' => 'TOKEN456',
        'client_token' => 'CLIENT789',
        ...$overrides,
    ];
}

test('guests cannot save WhatsApp provider credentials', function () {
    $this->put(route('whatsapp.providers.update', 'zapi'), zApiCredentialsPayload())
        ->assertRedirect(route('login'));

    expect(WhatsAppProviderCredential::count())->toBe(0);
});

test('verified credentials are stored encrypted with a webhook secret', function () {
    Http::preventStrayRequests();
    Http::fake([ZAPI_STATUS_URL => Http::response(['connected' => true, 'smartphoneConnected' => true])]);

    $this->actingAs(User::factory()->create())
        ->put(route('whatsapp.providers.update', 'zapi'), zApiCredentialsPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('whatsapp.index'));

    Http::assertSent(fn (Request $request) => $request->url() === ZAPI_STATUS_URL
        && $request->hasHeader('Client-Token', 'CLIENT789'));

    $credential = WhatsAppProviderCredential::sole();

    expect($credential->credentials)->toBe(zApiCredentialsPayload())
        ->and($credential->webhook_secret)->toHaveLength(48)
        ->and(DB::table('whats_app_provider_credentials')->value('credentials'))->not->toContain('TOKEN456');
});

test('credentials that do not work are not stored', function (int $status, array $body, string $message) {
    Http::preventStrayRequests();
    Http::fake([ZAPI_STATUS_URL => Http::response($body, $status)]);

    $this->actingAs(User::factory()->create())
        ->put(route('whatsapp.providers.update', 'zapi'), zApiCredentialsPayload())
        ->assertSessionHasErrors(['instance_id' => $message]);

    expect(WhatsAppProviderCredential::count())->toBe(0);
})->with([
    'invalid token' => [400, ['error' => 'Instance not found'], 'ID da instância, token ou Client-Token inválidos.'],
    'whatsapp disconnected' => [200, ['connected' => false, 'error' => 'You are not connected.'], 'A instância não está conectada ao WhatsApp. Leia o QR Code no painel da Z-API e tente novamente.'],
]);

test('the client token is optional', function () {
    Http::preventStrayRequests();
    Http::fake([ZAPI_STATUS_URL => Http::response(['connected' => true])]);

    $this->actingAs(User::factory()->create())
        ->put(route('whatsapp.providers.update', 'zapi'), zApiCredentialsPayload(['client_token' => '']))
        ->assertSessionHasNoErrors();

    Http::assertSent(fn (Request $request) => ! $request->hasHeader('Client-Token'));
});

test('the stored instance token is kept when it is left blank', function () {
    Http::preventStrayRequests();
    Http::fake([ZAPI_STATUS_URL => Http::response(['connected' => true])]);
    $credential = WhatsAppProviderCredential::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('whatsapp.providers.update', 'zapi'), zApiCredentialsPayload(['instance_token' => '']))
        ->assertSessionHasNoErrors();

    expect($credential->refresh()->credentials['instance_token'])->toBe('TOKEN456');
});

test('the webhook secret is kept when the credentials are updated', function () {
    Http::preventStrayRequests();
    Http::fake([ZAPI_STATUS_URL => Http::response(['connected' => true])]);
    $credential = WhatsAppProviderCredential::factory()->create();
    $webhookSecret = $credential->webhook_secret;

    $this->actingAs(User::factory()->create())
        ->put(route('whatsapp.providers.update', 'zapi'), zApiCredentialsPayload())
        ->assertSessionHasNoErrors();

    expect($credential->refresh()->webhook_secret)->toBe($webhookSecret);
});
