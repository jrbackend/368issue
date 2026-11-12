<?php

use App\Models\TrafficRule;
use App\Models\TrafficVisit;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('traffic.logs'))->assertRedirect(route('login'));
});

test('the traffic log lists the latest visits first with the stats of the last 24 hours', function () {
    TrafficVisit::factory()->create(['ip' => '200.0.0.1', 'created_at' => now()->subDays(2)]);
    TrafficVisit::factory()->create(['ip' => '200.0.0.2']);
    TrafficVisit::factory()->blocked(TrafficRule::REASON_DEVICE)->create(['ip' => '200.0.0.3']);

    $this->actingAs(User::factory()->create())
        ->get(route('traffic.logs'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('TrafficLogs')
            ->has('visits.data', 3)
            ->where('visits.data.0.ip', '200.0.0.3')
            ->where('visits.data.0.was_allowed', false)
            ->where('visits.data.0.blocked_reason', TrafficRule::REASON_DEVICE)
            ->where('stats', ['total' => 2, 'allowed' => 1, 'blocked' => 1])
        );
});

test('the traffic log can be filtered by status', function (string $status, string $expectedIp) {
    TrafficVisit::factory()->create(['ip' => '200.0.0.1']);
    TrafficVisit::factory()->blocked()->create(['ip' => '200.0.0.2']);

    $this->actingAs(User::factory()->create())
        ->get(route('traffic.logs', ['status' => $status]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('visits.data', 1)
            ->where('visits.data.0.ip', $expectedIp)
            ->where('filters.status', $status)
        );
})->with([
    'allowed' => ['allowed', '200.0.0.1'],
    'blocked' => ['blocked', '200.0.0.2'],
]);

test('the traffic log can be searched by ip, city, region or country', function (string $search) {
    TrafficVisit::factory()->create(['ip' => '200.0.0.1', 'city_name' => 'Campinas', 'region_name' => 'São Paulo', 'country_code' => 'BR']);
    TrafficVisit::factory()->create(['ip' => '8.8.8.8', 'city_name' => 'Chicago', 'region_name' => 'Illinois', 'country_code' => 'US']);

    $this->actingAs(User::factory()->create())
        ->get(route('traffic.logs', ['search' => $search]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('visits.data', 1)
            ->where('visits.data.0.ip', '200.0.0.1')
        );
})->with([
    'ip' => ['200.0'],
    'city' => ['Campi'],
    'region' => ['São'],
    'country' => ['br'],
]);

test('visits older than the retention period are pruned', function () {
    TrafficVisit::factory()->create(['created_at' => now()->subDays(31)]);
    $recentVisit = TrafficVisit::factory()->create(['created_at' => now()->subDays(29)]);

    $this->artisan('model:prune', ['--model' => [TrafficVisit::class]])->assertSuccessful();

    expect(TrafficVisit::pluck('id')->all())->toBe([$recentVisit->id]);
});
