<?php

namespace Database\Factories;

use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WhatsAppCampaignRecipient>
 */
class WhatsAppCampaignRecipientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'whats_app_campaign_id' => WhatsAppCampaign::factory(),
            'phone' => '55119'.fake()->unique()->numerify('########'),
            'status' => WhatsAppCampaignRecipient::STATUS_QUEUED,
        ];
    }

    /**
     * Indicate that the message was sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WhatsAppCampaignRecipient::STATUS_SENT,
            'provider_message_id' => Str::upper(Str::random(20)),
            'sent_at' => now(),
        ]);
    }

    /**
     * Indicate that the message was sent and read.
     */
    public function read(): static
    {
        return $this->sent()->state(fn (array $attributes) => [
            'delivered_at' => now(),
            'read_at' => now(),
        ]);
    }

    /**
     * Indicate that the message could not be sent.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WhatsAppCampaignRecipient::STATUS_FAILED,
            'error' => 'Z-API (400): phone not exists.',
        ]);
    }
}
