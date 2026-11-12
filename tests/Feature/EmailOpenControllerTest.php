<?php

use App\Models\EmailCampaignRecipient;

test('opening the e-mail records the first time it was read', function () {
    $this->freezeTime();
    $recipient = EmailCampaignRecipient::factory()->sent()->create();

    $this->get(route('email.open', $recipient->tracking_token))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/gif');

    expect($recipient->refresh()->opened_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('opening the e-mail again keeps the first read time', function () {
    $firstOpenedAt = now()->subDay()->startOfSecond();
    $recipient = EmailCampaignRecipient::factory()->sent()->create(['opened_at' => $firstOpenedAt]);

    $this->get(route('email.open', $recipient->tracking_token))->assertOk();

    expect($recipient->refresh()->opened_at->toDateTimeString())->toBe($firstOpenedAt->toDateTimeString());
});

test('an unknown tracking token still returns the pixel', function () {
    $this->get(route('email.open', '9b2f4c1e-0000-4000-8000-000000000000'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/gif');
});
