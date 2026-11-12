<?php

use App\Jobs\SendWhatsAppMessage;
use App\Models\User;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use App\Models\WhatsAppProviderCredential;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('whatsapp.index'))->assertRedirect(route('login'));
    $this->post(route('whatsapp.campaigns.store'), [])->assertRedirect(route('login'));
});

test('the whatsapp page lists the providers with the webhook url without exposing secrets', function () {
    $credential = WhatsAppProviderCredential::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('whatsapp.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('WhatsApp')
            ->where('providers.0.key', 'zapi')
            ->where('providers.0.label', 'Z-API')
            ->where('providers.0.configured', true)
            ->where('providers.0.fields.0.value', 'INSTANCE123')
            ->where('providers.0.fields.1.value', '')
            ->where('providers.0.fields.2.value', '')
            ->where('providers.0.webhookUrl', route('whatsapp.webhooks.status', ['zapi', $credential->webhook_secret]))
            ->where('maxRecipients', 500)
            ->where('defaultMessage', fn (string $message) => str_contains($message, '*bet368.cassino* agora é *bet368.games*')
                && str_contains($message, 'https://bet368.games'))
        );
});

test('the whatsapp page shows the delivery stats', function () {
    $campaign = WhatsAppCampaign::factory()->create(['recipients_count' => 5]);
    WhatsAppCampaignRecipient::factory()->for($campaign, 'campaign')->sent()->create();
    WhatsAppCampaignRecipient::factory()->for($campaign, 'campaign')->read()->create();
    WhatsAppCampaignRecipient::factory()->for($campaign, 'campaign')->count(2)->create();
    WhatsAppCampaignRecipient::factory()->for($campaign, 'campaign')->failed()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('whatsapp.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats', ['sent' => 2, 'queued' => 2, 'failed' => 1, 'delivered' => 1, 'read' => 1])
            ->where('recentCampaigns.0.sent_count', 2)
            ->where('recentCampaigns.0.read_count', 1)
            ->where('recentCampaigns.0.failed_count', 1)
        );
});

test('a campaign queues one message per unique number, spread out over time', function () {
    $this->freezeTime();
    config(['whatsapp.seconds_between_messages' => 6]);
    Queue::fake([SendWhatsAppMessage::class]);
    WhatsAppProviderCredential::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('whatsapp.campaigns.store'), [
            'provider' => 'zapi',
            'message' => 'Mudamos!',
            'phones' => "(11) 99999-1234\n5511999991234\n+351 912 345 678\r\n0044 20 7946 0958\n\n",
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('whatsapp.index'));

    $campaign = WhatsAppCampaign::sole();

    expect($campaign)
        ->user_id->toBe($user->id)
        ->message->toBe('Mudamos!')
        ->recipients_count->toBe(3)
        ->and($campaign->recipients()->pluck('phone')->all())->toBe(['5511999991234', '351912345678', '442079460958']);

    Queue::assertPushedTimes(SendWhatsAppMessage::class, 3);
    Queue::assertPushed(SendWhatsAppMessage::class, fn (SendWhatsAppMessage $job) => $job->recipient->phone === '5511999991234'
        && $job->delay->equalTo(now()));
    Queue::assertPushed(SendWhatsAppMessage::class, fn (SendWhatsAppMessage $job) => $job->recipient->phone === '442079460958'
        && $job->delay->equalTo(now()->addSeconds(12)));
});

test('a campaign cannot be sent before the provider credentials are configured', function () {
    Queue::fake([SendWhatsAppMessage::class]);

    $this->actingAs(User::factory()->create())
        ->post(route('whatsapp.campaigns.store'), [
            'provider' => 'zapi',
            'message' => 'Mudamos!',
            'phones' => '11999991234',
        ])
        ->assertSessionHasErrors(['provider' => 'Configure as credenciais do serviço de WhatsApp antes de disparar.']);

    expect(WhatsAppCampaign::count())->toBe(0);
    Queue::assertNothingPushed();
});

test('the phone list is validated', function (string $phones, string $message) {
    Queue::fake([SendWhatsAppMessage::class]);
    WhatsAppProviderCredential::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('whatsapp.campaigns.store'), [
            'provider' => 'zapi',
            'message' => 'Mudamos!',
            'phones' => $phones,
        ])
        ->assertSessionHasErrors(['phones' => $message]);

    expect(WhatsAppCampaign::count())->toBe(0);
    Queue::assertNothingPushed();
})->with([
    'empty list' => ['', 'Informe pelo menos um número.'],
    'invalid numbers' => ["11999991234\n12345\njoao@example.com", 'Números inválidos: 12345, joao@example.com.'],
    'more than 500 numbers' => [
        fn () => implode("\n", array_map(fn (int $number) => '5511'.(900000000 + $number), range(1, 501))),
        'Máximo de 500 números por disparo. A lista tem 501 números únicos.',
    ],
]);
