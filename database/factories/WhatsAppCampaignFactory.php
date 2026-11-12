<?php

namespace Database\Factories;

use App\Models\WhatsAppCampaign;
use App\WhatsApp\Providers\ZApiProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppCampaign>
 */
class WhatsAppCampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'provider' => ZApiProvider::key(),
            'message' => 'Mudamos para bet368.games!',
            'recipients_count' => 1,
        ];
    }
}
