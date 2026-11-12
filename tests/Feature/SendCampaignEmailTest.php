<?php

use App\Jobs\SendCampaignEmail;
use App\Mail\Providers\EmailDeliveryException;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailProviderCredential;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const SENDGRID_SEND_URL = 'https://api.sendgrid.com/v3/mail/send';

test('the domain change e-mail is sent through SendGrid with an open tracking pixel', function () {
    Http::preventStrayRequests();
    Http::fake([SENDGRID_SEND_URL => Http::response(null, 202, ['X-Message-Id' => 'msg-123'])]);
    EmailProviderCredential::factory()->create();
    $recipient = EmailCampaignRecipient::factory()
        ->for(EmailCampaign::factory()->state(['subject' => 'Mudamos!']), 'campaign')
        ->create(['email' => 'joao@example.com']);

    SendCampaignEmail::dispatchSync($recipient);

    Http::assertSent(function (Request $request) use ($recipient) {
        $html = $request['content'][0]['value'];

        return $request->url() === SENDGRID_SEND_URL
            && $request->hasHeader('Authorization', 'Bearer SG.test-key')
            && $request['personalizations'][0]['to'][0]['email'] === 'joao@example.com'
            && $request['from'] === ['email' => 'avisos@bet368.games', 'name' => 'bet368']
            && $request['subject'] === 'Mudamos!'
            && str_contains($html, 'bet368.games')
            && str_contains($html, route('email.open', $recipient->tracking_token));
    });

    expect($recipient->refresh())
        ->status->toBe(EmailCampaignRecipient::STATUS_SENT)
        ->provider_message_id->toBe('msg-123')
        ->sent_at->not->toBeNull();
});

test('the recipient is marked as failed when SendGrid rejects the e-mail', function () {
    Http::preventStrayRequests();
    Http::fake([SENDGRID_SEND_URL => Http::response([
        'errors' => [['message' => 'The from address does not match a verified Sender Identity.']],
    ], 403)]);
    EmailProviderCredential::factory()->create();
    $recipient = EmailCampaignRecipient::factory()->create();

    expect(fn () => SendCampaignEmail::dispatchSync($recipient))->toThrow(EmailDeliveryException::class);

    expect($recipient->refresh())
        ->status->toBe(EmailCampaignRecipient::STATUS_FAILED)
        ->error->toBe('SendGrid (403): The from address does not match a verified Sender Identity.');
});

test('the recipient is marked as failed when the provider credentials are missing', function () {
    Http::preventStrayRequests();
    $recipient = EmailCampaignRecipient::factory()->create();

    SendCampaignEmail::dispatchSync($recipient);

    expect($recipient->refresh())
        ->status->toBe(EmailCampaignRecipient::STATUS_FAILED)
        ->error->toBe('As credenciais do serviço de e-mail não estão configuradas.');

    Http::assertNothingSent();
});

test('a recipient that was already sent is not sent again', function () {
    Http::preventStrayRequests();
    EmailProviderCredential::factory()->create();
    $recipient = EmailCampaignRecipient::factory()->sent()->create();

    SendCampaignEmail::dispatchSync($recipient);

    Http::assertNothingSent();
});
