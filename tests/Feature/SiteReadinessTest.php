<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SiteReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_every_admin_page_renders(): void
    {
        $admin = User::first();
        $order = Order::create([
            'order_number' => 'ARM-TEST-002',
            'customer_name' => 'Test',
            'customer_phone' => '01805962220',
            'customer_address' => 'Dhaka',
            'delivery_area' => 'inside',
            'delivery_charge' => 150,
            'subtotal' => 100,
            'grand_total' => 250,
        ]);
        $product = Product::first();

        foreach ([
            '/admin',
            '/admin/orders',
            "/admin/orders/{$order->id}",
            '/admin/products',
            '/admin/products/create',
            "/admin/products/{$product->id}/edit",
            '/admin/settings',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_login_form_has_no_prefilled_credentials_and_hides_register_link(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('admin@armetalbd.com');
        $response->assertDontSee(route('register'));
    }

    public function test_registration_is_closed_once_an_admin_exists(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
    }

    public function test_password_reset_link_is_emailed_and_never_shown_on_page(): void
    {
        Notification::fake();

        $response = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'admin@armetalbd.com']);

        $response->assertSessionHas('status');
        $response->assertSessionMissing('reset_url');
        Notification::assertSentTo(User::first(), ResetPassword::class);
    }

    public function test_password_can_be_reset_with_emailed_token(): void
    {
        Notification::fake();
        $this->post('/forgot-password', ['email' => 'admin@armetalbd.com']);

        $token = null;
        Notification::assertSentTo(User::first(), ResetPassword::class, function (ResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'admin@armetalbd.com',
            'password' => 'N3w-Str0ng-Passw0rd!',
            'password_confirmation' => 'N3w-Str0ng-Passw0rd!',
        ])->assertRedirect(route('login'));

        $this->post('/login', ['email' => 'admin@armetalbd.com', 'password' => 'N3w-Str0ng-Passw0rd!'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_order_ignores_inactive_products_and_hides_internal_errors(): void
    {
        $product = Product::first();
        $product->update(['is_active' => false]);

        $this->postJson('/order', [
            'customer_name' => 'Test',
            'customer_phone' => '01805962220',
            'customer_address' => 'Dhaka',
            'delivery_area' => 'inside',
            'items' => [['id' => $product->id, 'qty' => 1]],
        ])->assertStatus(422)->assertJson(['success' => false]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_settings_only_accept_known_keys(): void
    {
        $this->actingAs(User::first())->post('/admin/settings', [
            'phone' => '01900000000',
            'evil_key' => 'x',
        ])->assertRedirect();

        $this->assertDatabaseMissing('settings', ['key' => 'evil_key']);
    }

    public function test_admin_can_change_password_from_profile(): void
    {
        $admin = User::first();

        $this->actingAs($admin)->get('/admin/profile')->assertOk();

        $this->actingAs($admin)->put('/admin/profile/password', [
            'current_password' => 'wrong',
            'password' => 'N3w-Str0ng-Passw0rd!',
            'password_confirmation' => 'N3w-Str0ng-Passw0rd!',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($admin)->put('/admin/profile/password', [
            'current_password' => 'password',
            'password' => 'N3w-Str0ng-Passw0rd!',
            'password_confirmation' => 'N3w-Str0ng-Passw0rd!',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertTrue(Hash::check('N3w-Str0ng-Passw0rd!', $admin->fresh()->password));
    }

    public function test_admin_can_update_profile_info(): void
    {
        $this->actingAs(User::first())->patch('/admin/profile', [
            'name' => 'New Name',
            'email' => 'new@armetalbd.com',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'new@armetalbd.com', 'name' => 'New Name']);
    }

    public function test_delete_buttons_use_confirmation_popup_instead_of_browser_confirm(): void
    {
        $admin = User::first();
        Order::create([
            'order_number' => 'ARM-TEST-003',
            'customer_name' => 'Test',
            'customer_phone' => '01805962220',
            'customer_address' => 'Dhaka',
            'delivery_area' => 'inside',
            'delivery_charge' => 150,
            'subtotal' => 100,
            'grand_total' => 250,
        ]);

        foreach (['/admin/products', '/admin/orders'] as $url) {
            $this->actingAs($admin)->get($url)
                ->assertSee('data-confirm=', false)
                ->assertSee('id="confirmOverlay"', false)
                ->assertDontSee('return confirm(', false);
        }
    }

    public function test_orders_and_products_lists_are_paginated(): void
    {
        $admin = User::first();

        for ($i = 1; $i <= 20; $i++) {
            Order::create([
                'order_number' => "ARM-PAGE-{$i}",
                'customer_name' => "Customer {$i}",
                'customer_phone' => '01805962220',
                'customer_address' => 'Dhaka',
                'delivery_area' => 'inside',
                'delivery_charge' => 150,
                'subtotal' => 100,
                'grand_total' => 250,
            ]);
        }

        $page1 = $this->actingAs($admin)->get('/admin/orders');
        $page1->assertOk()->assertSee('class="pager"', false)->assertSee('page=2', false)->assertSee('মোট');
        $this->assertCount(15, $page1->viewData('orders')->items());

        $page2 = $this->actingAs($admin)->get('/admin/orders?page=2');
        $page2->assertOk();
        $this->assertCount(5, $page2->viewData('orders')->items());

        $this->actingAs($admin)->get('/admin/orders?status=pending&page=2')->assertOk()->assertSee('status=pending', false);

        foreach (range(1, 10) as $i) {
            Product::create(['name' => "Extra {$i}", 'slug' => "extra-{$i}", 'price' => 100]);
        }
        $this->actingAs($admin)->get('/admin/products')->assertOk()->assertSee('page=2', false);
    }
}
