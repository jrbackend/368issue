<?php

use App\Models\EmailProviderCredential;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

const SENDGRID_SCOPES_URL = 'https://api.sendgrid.com/v3/scopes';

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function twilioCredentialsPayload(array $overrides = []): array
{
    return [
        'api_key' => 'SG.new-key',
        'from_email' => 'avisos@bet368.games',
        'from_name' => 'bet368',
        ...$overrides,
    ];
}

test('guests cannot save provider credentials', function () {
    $this->put(route('email.providers.update', 'twilio'), twilioCredentialsPayload())
        ->assertRedirect(route('login'));

    expect(EmailProviderCredential::count())->toBe(0);
});

test('verified credentials are stored encrypted', function () {
    Http::preventStrayRequests();
    Http::fake([SENDGRID_SCOPES_URL => Http::response(['scopes' => ['mail.send', 'alerts.read']])]);

    $this->actingAs(User::factory()->create())
        ->put(route('email.providers.update', 'twilio'), twilioCredentialsPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('email.index'));

    Http::assertSent(fn (Request $request) => $request->url() === SENDGRID_SCOPES_URL
        && $request->hasHeader('Authorization', 'Bearer SG.new-key'));

    expect(EmailProviderCredential::sole()->credentials)->toBe(twilioCredentialsPayload())
        ->and(DB::table('email_provider_credentials')->value('credentials'))->not->toContain('SG.new-key');
});

test('credentials rejected by the provider are not stored', function (int $status, array $body, string $message) {
    Http::preventStrayRequests();
    Http::fake([SENDGRID_SCOPES_URL => Http::response($body, $status)]);

    $this->actingAs(User::factory()->create())
        ->put(route('email.providers.update', 'twilio'), twilioCredentialsPayload())
        ->assertSessionHasErrors(['api_key' => $message]);

    expect(EmailProviderCredential::count())->toBe(0);
})->with([
    'invalid key' => [401, ['errors' => [['message' => 'authorization required']]], 'A API Key foi recusada pelo SendGrid.'],
    'key without mail send permission' => [200, ['scopes' => ['alerts.read']], 'A API Key não tem a permissão "Mail Send".'],
]);

test('the stored api key is kept when it is left blank', function () {
    Http::preventStrayRequests();
    Http::fake([SENDGRID_SCOPES_URL => Http::response(['scopes' => ['mail.send']])]);
    EmailProviderCredential::factory()->create(['credentials' => twilioCredentialsPayload(['api_key' => 'SG.stored-key'])]);

    $this->actingAs(User::factory()->create())
        ->put(route('email.providers.update', 'twilio'), twilioCredentialsPayload([
            'api_key' => '',
            'from_name' => 'bet368 Oficial',
        ]))
        ->assertSessionHasNoErrors();

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer SG.stored-key'));

    expect(EmailProviderCredential::sole()->credentials)
        ->toMatchArray(['api_key' => 'SG.stored-key', 'from_name' => 'bet368 Oficial']);
});

test('the credentials are validated before being checked with the provider', function () {
    Http::preventStrayRequests();

    $this->actingAs(User::factory()->create())
        ->put(route('email.providers.update', 'twilio'), [
            'api_key' => 'not-a-sendgrid-key',
            'from_email' => 'invalid',
            'from_name' => '',
        ])
        ->assertSessionHasErrors([
            'api_key' => 'API Key deve começar com SG..',
            'from_email' => 'Informe um e-mail válido em E-mail do remetente.',
            'from_name' => 'Informe Nome do remetente.',
        ]);

    Http::assertNothingSent();
});

test('unknown providers return 404', function () {
    $this->actingAs(User::factory()->create())
        ->put('/email/providers/unknown', twilioCredentialsPayload())
        ->assertNotFound();
});
