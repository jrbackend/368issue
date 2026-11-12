<?php

use App\Jobs\SendCampaignEmail;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailProviderCredential;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('email.index'))->assertRedirect(route('login'));
    $this->post(route('email.campaigns.store'), [])->assertRedirect(route('login'));
});

test('the e-mail page lists the providers without exposing stored secrets', function () {
    EmailProviderCredential::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('email.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Email')
            ->where('providers.0.key', 'twilio')
            ->where('providers.0.label', 'Twilio SendGrid')
            ->where('providers.0.configured', true)
            ->where('providers.0.fields.0.name', 'api_key')
            ->where('providers.0.fields.0.value', '')
            ->where('providers.0.fields.1.value', 'avisos@bet368.games')
            ->where('maxRecipients', 1000)
            ->where('defaultSubject', 'Atenção: bet368.cassino agora é bet368.games')
        );
});

test('the e-mail page shows the delivery stats', function () {
    $campaign = EmailCampaign::factory()->create(['recipients_count' => 6]);
    EmailCampaignRecipient::factory()->for($campaign, 'campaign')->sent()->count(2)->create();
    EmailCampaignRecipient::factory()->for($campaign, 'campaign')->opened()->create();
    EmailCampaignRecipient::factory()->for($campaign, 'campaign')->count(2)->create();
    EmailCampaignRecipient::factory()->for($campaign, 'campaign')->failed()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('email.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats', ['sent' => 3, 'queued' => 2, 'failed' => 1, 'opened' => 1])
            ->where('recentCampaigns.0.sent_count', 3)
            ->where('recentCampaigns.0.opened_count', 1)
            ->where('recentCampaigns.0.failed_count', 1)
        );
});

test('a campaign queues one e-mail per unique address', function () {
    Queue::fake([SendCampaignEmail::class]);
    EmailProviderCredential::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('email.campaigns.store'), [
            'provider' => 'twilio',
            'subject' => 'Mudamos!',
            'emails' => "  Joao@Example.com \njoao@example.com\n\nmaria@example.com\r\n",
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('email.index'));

    $campaign = EmailCampaign::sole();

    expect($campaign)
        ->user_id->toBe($user->id)
        ->provider->toBe('twilio')
        ->subject->toBe('Mudamos!')
        ->recipients_count->toBe(2)
        ->and($campaign->recipients()->pluck('email')->all())->toBe(['joao@example.com', 'maria@example.com'])
        ->and($campaign->recipients()->where('status', EmailCampaignRecipient::STATUS_QUEUED)->count())->toBe(2);

    Queue::assertPushedTimes(SendCampaignEmail::class, 2);
});

test('a campaign cannot be sent before the provider credentials are configured', function () {
    Queue::fake([SendCampaignEmail::class]);

    $this->actingAs(User::factory()->create())
        ->post(route('email.campaigns.store'), [
            'provider' => 'twilio',
            'subject' => 'Mudamos!',
            'emails' => 'joao@example.com',
        ])
        ->assertSessionHasErrors(['provider' => 'Configure as credenciais do serviço de e-mail antes de disparar.']);

    expect(EmailCampaign::count())->toBe(0);
    Queue::assertNothingPushed();
});

test('the e-mail list is validated', function (string $emails, string $message) {
    Queue::fake([SendCampaignEmail::class]);
    EmailProviderCredential::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('email.campaigns.store'), [
            'provider' => 'twilio',
            'subject' => 'Mudamos!',
            'emails' => $emails,
        ])
        ->assertSessionHasErrors(['emails' => $message]);

    expect(EmailCampaign::count())->toBe(0);
    Queue::assertNothingPushed();
})->with([
    'empty list' => ['', 'Informe pelo menos um e-mail.'],
    'invalid addresses' => ["joao@example.com\nnot-an-email\nmaria@", 'E-mails inválidos: not-an-email, maria@.'],
    'more than 1,000 addresses' => [
        fn () => implode("\n", array_map(fn (int $number) => "user{$number}@example.com", range(1, 1001))),
        'Máximo de 1.000 e-mails por disparo. A lista tem 1.001 e-mails únicos.',
    ],
]);
