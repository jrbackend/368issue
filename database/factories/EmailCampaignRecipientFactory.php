<?php

namespace Database\Factories;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailCampaignRecipient>
 */
class EmailCampaignRecipientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email_campaign_id' => EmailCampaign::factory(),
            'email' => fake()->unique()->safeEmail(),
            'status' => EmailCampaignRecipient::STATUS_QUEUED,
            'tracking_token' => (string) Str::uuid(),
        ];
    }

    /**
     * Indicate that the e-mail was sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmailCampaignRecipient::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Indicate that the e-mail was sent and opened.
     */
    public function opened(): static
    {
        return $this->sent()->state(fn (array $attributes) => [
            'opened_at' => now(),
        ]);
    }

    /**
     * Indicate that the e-mail could not be sent.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmailCampaignRecipient::STATUS_FAILED,
            'error' => 'SendGrid (400): The from address does not match a verified Sender Identity.',
        ]);
    }
}
