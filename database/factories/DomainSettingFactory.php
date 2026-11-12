<?php

namespace Database\Factories;

use App\Models\DomainSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DomainSetting>
 */
class DomainSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $newDomain = fake()->unique()->domainName();

        return [
            'old_domain' => fake()->unique()->domainName(),
            'new_domain' => $newDomain,
            'new_domain_url' => "https://{$newDomain}",
            'redirect_seconds' => fake()->numberBetween(5, 30),
        ];
    }
}
