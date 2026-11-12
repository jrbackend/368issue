<?php

use App\Models\TrafficRule;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function trafficRulePayload(array $overrides = []): array
{
    return [
        'is_enabled' => true,
        'allowed_devices' => ['mobile'],
        'allowed_languages' => ['pt'],
        'allowed_countries' => ['BR'],
        'allow_unknown_country' => false,
        'blocked_action' => TrafficRule::ACTION_BLOCK,
        'blocked_redirect_url' => null,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('traffic.edit'))->assertRedirect(route('login'));
    $this->put(route('traffic.update'), trafficRulePayload())->assertRedirect(route('login'));

    expect(TrafficRule::current()->is_enabled)->toBeFalse();
});

test('the traffic control page shows the current rules and the available options', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('traffic.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Traffic')
            ->where('trafficRule.is_enabled', false)
            ->where('trafficRule.allowed_devices', [])
            ->where('deviceOptions', ['desktop', 'mobile', 'tablet'])
            ->has('languageOptions')
            ->has('countryOptions', 249)
        );
});

test('authenticated users can update the traffic rules', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('traffic.update'), trafficRulePayload([
            'allowed_countries' => ['BR', 'PT', 'BR'],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('traffic.edit'));

    expect(TrafficRule::current())
        ->is_enabled->toBeTrue()
        ->allowed_devices->toBe(['mobile'])
        ->allowed_languages->toBe(['pt'])
        ->allowed_countries->toBe(['BR', 'PT'])
        ->allow_unknown_country->toBeFalse()
        ->blocked_action->toBe(TrafficRule::ACTION_BLOCK);
});

test('the redirect url is discarded when blocked visitors are not redirected', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('traffic.update'), trafficRulePayload([
            'blocked_redirect_url' => 'https://example.com',
        ]))
        ->assertSessionHasNoErrors();

    expect(TrafficRule::current()->blocked_redirect_url)->toBeNull();
});

test('the traffic rules are validated', function (array $overrides, string $field, string $message) {
    $this->actingAs(User::factory()->create())
        ->put(route('traffic.update'), trafficRulePayload($overrides))
        ->assertSessionHasErrors([$field => $message]);

    expect(TrafficRule::current()->is_enabled)->toBeFalse();
})->with([
    'unknown device' => [['allowed_devices' => ['smartwatch']], 'allowed_devices.0', 'Selecione apenas dispositivos válidos.'],
    'unknown language' => [['allowed_languages' => ['xx']], 'allowed_languages.0', 'Selecione apenas idiomas válidos.'],
    'unknown country' => [['allowed_countries' => ['ZZ']], 'allowed_countries.0', 'Selecione apenas países válidos.'],
    'redirect without url' => [['blocked_action' => TrafficRule::ACTION_REDIRECT], 'blocked_redirect_url', 'Informe a URL para onde os visitantes bloqueados serão enviados.'],
    'redirect to non https url' => [['blocked_action' => TrafficRule::ACTION_REDIRECT, 'blocked_redirect_url' => 'http://example.com'], 'blocked_redirect_url', 'Informe uma URL válida começando com https://.'],
]);
