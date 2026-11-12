<?php

namespace Database\Factories;

use App\Models\TrafficRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrafficRule>
 */
class TrafficRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_enabled' => true,
            'allowed_devices' => [],
            'allowed_languages' => [],
            'allowed_countries' => [],
            'allow_unknown_country' => true,
            'blocked_action' => TrafficRule::ACTION_BLOCK,
            'blocked_redirect_url' => null,
        ];
    }

    /**
     * Indicate that blocked visitors are redirected to the given URL.
     */
    public function redirectingTo(string $url): static
    {
        return $this->state(fn (array $attributes) => [
            'blocked_action' => TrafficRule::ACTION_REDIRECT,
            'blocked_redirect_url' => $url,
        ]);
    }
}
