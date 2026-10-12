<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_landing_page_renders_with_database_products_and_pixel(): void
    {
        Setting::set('facebook_pixel_id', 'TEST_PIXEL_9999');

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('AR Metal BD');
        $response->assertSee('TEST_PIXEL_9999');
        $response->assertSee('সুইং চেয়ার');
    }

    public function test_customer_can_place_order_via_ajax(): void
    {
        $product = Product::first();

        $payload = [
            'customer_name' => 'মোঃ রাসেল হোসাইন',
            'customer_phone' => '01805962220',
            'customer_address' => 'বাড়ি ১২, রোড ৩, মিরপুর ঢাকা',
            'delivery_area' => 'inside',
            'order_note' => 'জলদি ডেলিভারি চাই',
            'items' => [
                ['id' => $product->id, 'qty' => 2],
            ],
        ];

        $response = $this->postJson('/order', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'customer_name' => 'মোঃ রাসেল হোসাইন',
            ]);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'মোঃ রাসেল হোসাইন',
            'customer_phone' => '01805962220',
            'delivery_area' => 'inside',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_admin_authentication_and_dashboard_access(): void
    {
        // Guest cannot access dashboard
        $guestResponse = $this->get('/admin');
        $guestResponse->assertRedirect('/login');

        // Admin login
        $loginResponse = $this->post('/login', [
            'email' => 'admin@armetalbd.com',
            'password' => 'password',
        ]);
        $loginResponse->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        // Dashboard renders
        $dashboardResponse = $this->get('/admin');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('ড্যাশবোর্ড ওভারভিউ');
    }

    public function test_admin_can_update_order_status(): void
    {
        $admin = User::first();
        $order = Order::create([
            'order_number' => 'ARM-TEST-001',
            'customer_name' => 'তানভীর',
            'customer_phone' => '01805962220',
            'customer_address' => 'উত্তরা ঢাকা',
            'delivery_area' => 'inside',
            'delivery_charge' => 150,
            'subtotal' => 13650,
            'grand_total' => 13800,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/orders/{$order->id}/status", [
            'status' => 'confirmed',
        ]);

        $response->assertRedirect();
        $this->assertEquals('confirmed', $order->fresh()->status);
    }

    public function test_admin_can_update_pixel_and_settings(): void
    {
        $admin = User::first();

        $response = $this->actingAs($admin)->post('/admin/settings', [
            'facebook_pixel_id' => '123456789012345',
            'phone' => '01900000000',
            'site_title' => 'Updated Site Title',
            'delivery_inside' => 160,
            'delivery_outside' => 320,
        ]);

        $response->assertRedirect();
        $this->assertEquals('123456789012345', Setting::get('facebook_pixel_id'));
        $this->assertEquals('01900000000', Setting::get('phone'));
    }
}
