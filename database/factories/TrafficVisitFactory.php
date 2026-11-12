<?php

namespace Database\Factories;

use App\Models\TrafficRule;
use App\Models\TrafficVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrafficVisit>
 */
class TrafficVisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ip' => fake()->ipv4(),
            'country_code' => 'BR',
            'region_name' => 'São Paulo',
            'city_name' => 'São Paulo',
            'device' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'is_bot' => false,
            'operating_system' => 'Android 14',
            'browser' => 'Chrome',
            'language' => 'pt',
            'user_agent' => fake()->userAgent(),
            'was_allowed' => true,
            'blocked_reason' => null,
        ];
    }

    /**
     * Indicate that the visit was blocked for the given reason.
     */
    public function blocked(string $reason = TrafficRule::REASON_COUNTRY): static
    {
        return $this->state(fn (array $attributes) => [
            'was_allowed' => false,
            'blocked_reason' => $reason,
        ]);
    }
}
