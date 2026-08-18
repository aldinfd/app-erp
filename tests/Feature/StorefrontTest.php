<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia;

it('renders the storefront home page for guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('storefront/home'));
});

it('renders the storefront home page for authenticated users without forcing login', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('storefront/home'));
});

it('only lists active products on the catalog', function () {
    $active = Product::factory()->create(['name' => 'Kopi Toraja']);
    Product::factory()->inactive()->create(['name' => 'Produk Nonaktif']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('storefront/home')
            ->has('products', 1)
            ->where('products.0.id', $active->id)
            ->where('products.0.name', 'Kopi Toraja'));
});

it('renders the cart page for guests', function () {
    $this->get(route('cart'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('storefront/cart'));
});
