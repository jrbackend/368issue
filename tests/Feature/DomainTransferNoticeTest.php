<?php

use App\Models\DomainSetting;
use Inertia\Testing\AssertableInertia as Assert;

test('the home page renders the domain transfer notice from the stored domain settings', function () {
    DomainSetting::current()->update([
        'old_domain' => 'old.example',
        'new_domain' => 'new.example',
        'new_domain_url' => 'https://new.example',
        'redirect_seconds' => 20,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('oldDomain', 'old.example')
            ->where('newDomain', 'new.example')
            ->where('newDomainUrl', 'https://new.example')
            ->where('redirectSeconds', 20)
        );
});
