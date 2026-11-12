<?php

namespace Database\Factories;

use App\Mail\Providers\TwilioSendGridProvider;
use App\Models\EmailCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailCampaign>
 */
class EmailCampaignFactory extends Factory
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
            'provider' => TwilioSendGridProvider::key(),
            'subject' => 'Mudamos para bet368.games',
            'recipients_count' => 1,
        ];
    }
}
