<?php

use App\Models\TrafficRule;
use App\Models\TrafficVisit;
use Inertia\Testing\AssertableInertia as Assert;

test('the blog is public', function () {
    $this->get(route('blog'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Blog'));
});

test('the blog is not affected by the traffic rules', function () {
    $this->withoutDefer();
    TrafficRule::current()->update(['is_enabled' => true, 'allowed_devices' => ['tablet']]);

    $this->get(route('blog'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Blog'));

    expect(TrafficVisit::count())->toBe(0);
});
