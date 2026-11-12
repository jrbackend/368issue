<?php

use App\Models\TrafficRule;
use App\Models\TrafficVisit;
use hisorange\BrowserDetect\Facade as Browser;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\LocationFake;
use Stevebauman\Location\LocationManager;
use Stevebauman\Location\Position;

const DESKTOP_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';
const MOBILE_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
const PUBLIC_IP = '200.147.67.142';
const TABLET_USER_AGENT = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

/**
 * @param  array<string, mixed>  $attributes
 */
function enableTrafficRules(array $attributes): void
{
    TrafficRule::current()->update(['is_enabled' => true, ...$attributes]);
}

test('everyone sees the home page when traffic control is disabled', function () {
    TrafficRule::current()->update([
        'is_enabled' => false,
        'allowed_devices' => ['tablet'],
        'allowed_languages' => ['ja'],
    ]);

    $this->withHeaders(['User-Agent' => DESKTOP_USER_AGENT, 'Accept-Language' => 'pt-BR'])
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
});

test('visitors are filtered by device', function (string $userAgent, array $allowedDevices, int $expectedStatus) {
    enableTrafficRules(['allowed_devices' => $allowedDevices]);

    $this->withHeaders(['User-Agent' => $userAgent])
        ->get(route('home'))
        ->assertStatus($expectedStatus);
})->with([
    'allowed desktop' => [DESKTOP_USER_AGENT, ['desktop'], 200],
    'blocked desktop' => [DESKTOP_USER_AGENT, ['mobile'], 403],
    'allowed mobile' => [MOBILE_USER_AGENT, ['mobile'], 200],
    'blocked mobile' => [MOBILE_USER_AGENT, ['desktop', 'tablet'], 403],
    'allowed tablet' => [TABLET_USER_AGENT, ['tablet'], 200],
    'blocked tablet' => [TABLET_USER_AGENT, ['mobile'], 403],
]);

test('visitors are filtered by the primary language of their browser', function (?string $acceptLanguage, int $expectedStatus) {
    enableTrafficRules(['allowed_languages' => ['pt']]);

    $this->withHeaders(array_filter(['Accept-Language' => $acceptLanguage]))
        ->get(route('home'))
        ->assertStatus($expectedStatus);
})->with([
    'brazilian portuguese' => ['pt-BR,pt;q=0.9,en;q=0.8', 200],
    'european portuguese' => ['pt-PT', 200],
    'english' => ['en-US,en;q=0.9,pt;q=0.8', 403],
    'no language header' => [null, 403],
]);

test('visitors are filtered by the country of their IP address', function (string $country, int $expectedStatus) {
    Location::fake([PUBLIC_IP => Position::make(['countryCode' => $country])]);
    enableTrafficRules(['allowed_countries' => ['BR', 'PT']]);

    $this->withServerVariables(['REMOTE_ADDR' => PUBLIC_IP])->get(route('home'))->assertStatus($expectedStatus);
})->with([
    'allowed country' => ['BR', 200],
    'blocked country' => ['US', 403],
]);

test('visitors whose country cannot be identified follow the unknown country setting', function (bool $allowUnknownCountry, int $expectedStatus) {
    Location::fake();
    enableTrafficRules(['allowed_countries' => ['BR'], 'allow_unknown_country' => $allowUnknownCountry]);

    $this->withServerVariables(['REMOTE_ADDR' => PUBLIC_IP])->get(route('home'))->assertStatus($expectedStatus);
})->with([
    'allowed when unknown' => [true, 200],
    'blocked when unknown' => [false, 403],
]);

test('the country of an IP address is cached between visits', function () {
    enableTrafficRules(['allowed_countries' => ['BR']]);

    Location::fake([PUBLIC_IP => Position::make(['countryCode' => 'BR'])]);
    $this->withServerVariables(['REMOTE_ADDR' => PUBLIC_IP])->get(route('home'))->assertOk();

    Location::swap(new LocationFake(new LocationManager, [
        PUBLIC_IP => Position::make(['countryCode' => 'US']),
    ]));
    $this->withServerVariables(['REMOTE_ADDR' => PUBLIC_IP])->get(route('home'))->assertOk();
});

test('blocked visitors see the content unavailable page', function () {
    enableTrafficRules(['allowed_devices' => ['mobile']]);

    $this->withHeaders(['User-Agent' => DESKTOP_USER_AGENT])
        ->get(route('home'))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('TrafficBlocked'));
});

test('blocked visitors are redirected when a redirect url is configured', function () {
    enableTrafficRules([
        'allowed_devices' => ['mobile'],
        'blocked_action' => TrafficRule::ACTION_REDIRECT,
        'blocked_redirect_url' => 'https://example.com/elsewhere',
    ]);

    $this->withHeaders(['User-Agent' => DESKTOP_USER_AGENT])
        ->get(route('home'))
        ->assertRedirect('https://example.com/elsewhere');
});

test('allowed visits are logged with the visitor location, device, operating system and browser', function () {
    $this->withoutDefer();
    Location::fake([PUBLIC_IP => Position::make([
        'countryCode' => 'BR',
        'regionName' => 'São Paulo',
        'cityName' => 'Campinas',
    ])]);

    $this->withServerVariables(['REMOTE_ADDR' => PUBLIC_IP])
        ->withHeaders(['User-Agent' => MOBILE_USER_AGENT, 'Accept-Language' => 'pt-BR,pt;q=0.9'])
        ->get(route('home'))
        ->assertOk();

    expect(TrafficVisit::sole())
        ->ip->toBe(PUBLIC_IP)
        ->country_code->toBe('BR')
        ->region_name->toBe('São Paulo')
        ->city_name->toBe('Campinas')
        ->device->toBe('mobile')
        ->operating_system->toBe('iOS 17')
        ->browser->toBe('Mobile Safari')
        ->language->toBe('pt')
        ->was_allowed->toBeTrue()
        ->blocked_reason->toBeNull();
});

test('blocked visits are logged with the reason they were blocked', function () {
    $this->withoutDefer();
    Location::fake([PUBLIC_IP => Position::make(['countryCode' => 'US', 'cityName' => 'Chicago'])]);
    enableTrafficRules(['allowed_countries' => ['BR']]);

    $this->withServerVariables(['REMOTE_ADDR' => PUBLIC_IP])
        ->withHeaders(['User-Agent' => DESKTOP_USER_AGENT])
        ->get(route('home'))
        ->assertForbidden();

    expect(TrafficVisit::sole())
        ->country_code->toBe('US')
        ->city_name->toBe('Chicago')
        ->device->toBe('desktop')
        ->operating_system->toBe('Windows 10')
        ->was_allowed->toBeFalse()
        ->blocked_reason->toBe(TrafficRule::REASON_COUNTRY);
});

test('visits from private IP addresses are logged without looking up their location', function () {
    $this->withoutDefer();
    Location::fake(['*' => Position::make(['countryCode' => 'BR'])]);

    $this->get(route('home'))->assertOk();

    expect(TrafficVisit::sole())
        ->ip->toBe('127.0.0.1')
        ->country_code->toBeNull();
});

test('the home page still works when the user agent parse result comes from a serializing cache', function () {
    config(['cache.stores.array.serialize' => true]);
    Cache::forgetDriver('array');

    $this->withHeaders(['User-Agent' => MOBILE_USER_AGENT])->get(route('home'))->assertOk();

    Browser::clearResolvedInstances();

    $this->withHeaders(['User-Agent' => MOBILE_USER_AGENT])->get(route('home'))->assertOk();
});
