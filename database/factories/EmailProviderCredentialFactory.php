<?php

namespace Database\Factories;

use App\Mail\Providers\TwilioSendGridProvider;
use App\Models\EmailProviderCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailProviderCredential>
 */
class EmailProviderCredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => TwilioSendGridProvider::key(),
            'credentials' => [
                'api_key' => 'SG.test-key',
                'from_email' => 'avisos@bet368.games',
                'from_name' => 'bet368',
            ],
        ];
    }
}
