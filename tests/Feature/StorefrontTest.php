<?php

use Inertia\Testing\AssertableInertia;

it('renders the storefront home page for guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('storefront/home'));
});
