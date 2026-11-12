<?php

namespace Database\Factories;

use App\Models\WhatsAppProviderCredential;
use App\WhatsApp\Providers\ZApiProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WhatsAppProviderCredential>
 */
class WhatsAppProviderCredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => ZApiProvider::key(),
            'credentials' => [
                'instance_id' => 'INSTANCE123',
                'instance_token' => 'TOKEN456',
                'client_token' => 'CLIENT789',
            ],
            'webhook_secret' => Str::random(48),
        ];
    }
}
