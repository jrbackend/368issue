<?php

use App\Models\DomainSetting;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the dashboard shows the current domain settings', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('domainSetting', [
                'old_domain' => 'bet368.cassino',
                'new_domain' => 'bet368.games',
                'new_domain_url' => 'https://bet368.games',
                'redirect_seconds' => 15,
            ])
        );
});

test('authenticated users can update the domain settings', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('domain-settings.update'), [
            'old_domain' => ' BET368.games ',
            'new_domain' => 'bet368.vip',
            'new_domain_url' => 'https://bet368.vip',
            'redirect_seconds' => 30,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(DomainSetting::current())
        ->old_domain->toBe('bet368.games')
        ->new_domain->toBe('bet368.vip')
        ->new_domain_url->toBe('https://bet368.vip')
        ->redirect_seconds->toBe(30);
});

test('guests cannot update the domain settings', function () {
    $this->put(route('domain-settings.update'), [
        'old_domain' => 'evil.example',
        'new_domain' => 'evil.games',
        'new_domain_url' => 'https://evil.games',
        'redirect_seconds' => 5,
    ])->assertRedirect(route('login'));

    expect(DomainSetting::current()->new_domain)->toBe('bet368.games');
});

test('the domain settings are validated', function (array $input, string $field, string $message) {
    $this->actingAs(User::factory()->create())
        ->put(route('domain-settings.update'), [
            'old_domain' => 'bet368.cassino',
            'new_domain' => 'bet368.games',
            'new_domain_url' => 'https://bet368.games',
            'redirect_seconds' => 15,
            ...$input,
        ])
        ->assertSessionHasErrors([$field => $message]);

    expect(DomainSetting::current()->new_domain)->toBe('bet368.games');
})->with([
    'missing new domain' => [['new_domain' => ''], 'new_domain', 'Informe o novo domínio.'],
    'domain with scheme' => [['new_domain' => 'https://bet368.games'], 'new_domain', 'Informe apenas o domínio, sem https:// ou barras (ex.: bet368.games).'],
    'same old and new domain' => [['old_domain' => 'bet368.games'], 'old_domain', 'O domínio antigo deve ser diferente do novo domínio.'],
    'non https url' => [['new_domain_url' => 'http://bet368.games'], 'new_domain_url', 'Informe uma URL válida começando com https://.'],
    'redirect too short' => [['redirect_seconds' => 2], 'redirect_seconds', 'O tempo de redirecionamento deve ficar entre 5 e 120 segundos.'],
]);
