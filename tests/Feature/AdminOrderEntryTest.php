<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminOrderEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'মোঃ রাসেল',
            'customer_phone' => '01805962220',
            'customer_address' => 'মিরপুর, ঢাকা',
            'delivery_area' => 'inside',
            'delivery_charge' => 150,
            'order_note' => 'সন্ধ্যায় কল দিন',
            'source' => 'phone',
            'status' => 'confirmed',
            'items' => [['id' => $product->id, 'qty' => 2, 'price' => $product->price]],
        ], $overrides);
    }

    private function fakeMeta(): void
    {
        Http::swap(new HttpFactory);
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1, 'fbtrace_id' => 'T'], 200)]);
        Setting::set('facebook_pixel_id', '1234567890');
        Setting::setSecret('meta_capi_token', 'EAAtesttokenvalue1234567890');
        Setting::set('meta_capi_enabled', '1');
    }

    // ---------- new order ----------

    public function test_new_order_page_lists_the_products_and_the_normal_delivery_charges(): void
    {
        Product::factory()->create(['name' => 'দোলনা চেয়ার', 'price' => 15500, 'stock' => 7]);
        Product::factory()->create(['name' => 'বন্ধ প্রোডাক্ট', 'is_active' => false]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.create'))->assertOk();

        $response->assertSee('নতুন অর্ডার যোগ করুন')->assertSee('window.ORDER_FORM', false)->assertSee('ফেসবুকে কনভার্সন পাঠান');

        // The product list travels inside the page as JSON, where Bangla letters are written as \u codes.
        $products = $response->viewData('products');
        $this->assertSame(['দোলনা চেয়ার'], array_column($products, 'name'));
        $this->assertSame(15500.0, $products[0]['price']);
        $this->assertSame(7, $products[0]['stock']);
        $this->assertSame(['inside' => 150.0, 'outside' => 300.0], $response->viewData('deliveryCharges'));
    }

    public function test_guests_cannot_reach_the_order_forms(): void
    {
        $order = Order::create(['order_number' => 'ARM-G-1', 'customer_name' => 'a', 'customer_phone' => '01805962220', 'customer_address' => 'a', 'delivery_area' => 'inside', 'delivery_charge' => 0, 'subtotal' => 1, 'grand_total' => 1, 'status' => 'pending']);

        $this->get(route('admin.orders.create'))->assertRedirect('/login');
        $this->post(route('admin.orders.store'), [])->assertRedirect('/login');
        $this->get(route('admin.orders.edit', $order))->assertRedirect('/login');
        $this->put(route('admin.orders.update', $order), [])->assertRedirect('/login');
    }

    public function test_admin_can_enter_a_phone_order_with_a_bargained_price_and_a_custom_delivery_charge(): void
    {
        $product = Product::factory()->create(['price' => 15500, 'stock' => 10]);

        $this->actingAs($this->admin)->post(route('admin.orders.store'), $this->payload($product, [
            'delivery_charge' => 100,
            'items' => [['id' => $product->id, 'qty' => 2, 'price' => 14000]],
        ]))->assertSessionHasNoErrors();

        $order = Order::with('items')->firstOrFail();
        $this->assertSame('phone', $order->source);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('01805962220', $order->customer_phone);
        $this->assertEqualsWithDelta(28000, (float) $order->subtotal, 0.001);
        $this->assertEqualsWithDelta(100, (float) $order->delivery_charge, 0.001);
        $this->assertEqualsWithDelta(28100, (float) $order->grand_total, 0.001);
        $this->assertSame(14000.0, (float) $order->items->first()->unit_price);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_the_phone_number_may_be_typed_in_bangla_or_with_the_country_code(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.orders.store'), $this->payload($product, ['customer_phone' => '+৮৮০১৮০৫৯৬২২২০']))
            ->assertSessionHasNoErrors();

        $this->assertSame('01805962220', Order::firstOrFail()->customer_phone);
    }

    public function test_an_order_cannot_be_saved_without_valid_details(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.orders.store'), $this->payload($product, ['customer_phone' => '12345', 'customer_name' => '', 'source' => 'carrier-pigeon', 'delivery_charge' => -5]))
            ->assertSessionHasErrors(['customer_phone', 'customer_name', 'source', 'delivery_charge']);

        $this->actingAs($this->admin)->post(route('admin.orders.store'), $this->payload($product, ['items' => []]))->assertSessionHasErrors('items');
        $this->actingAs($this->admin)->post(route('admin.orders.store'), $this->payload($product, ['items' => [['id' => 99999, 'qty' => 1, 'price' => 10]]]))->assertSessionHasErrors('items.0.id');

        $this->assertSame(0, Order::count());
    }

    public function test_lines_with_zero_quantity_are_dropped_and_an_order_of_only_zeros_is_refused(): void
    {
        $kept = Product::factory()->create(['stock' => 10]);
        $dropped = Product::factory()->create(['stock' => 10]);

        $this->actingAs($this->admin)->post(route('admin.orders.store'), $this->payload($kept, ['items' => [
            ['id' => $kept->id, 'qty' => 1, 'price' => 100],
            ['id' => $dropped->id, 'qty' => 0, 'price' => 100],
        ]]))->assertSessionHasNoErrors();

        $this->assertSame([$kept->id], Order::firstOrFail()->items->pluck('product_id')->all());
        $this->assertSame(10, $dropped->fresh()->stock);

        $this->actingAs($this->admin)->post(route('admin.orders.store'), $this->payload($kept, ['items' => [['id' => $kept->id, 'qty' => 0, 'price' => 100]]]))
            ->assertSessionHasErrors('items');
        $this->assertSame(1, Order::count());
    }

    public function test_the_same_product_picked_twice_becomes_one_line(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->actingAs($this->admin)->post(route('admin.orders.store'), $this->payload($product, ['items' => [
            ['id' => $product->id, 'qty' => 1, 'price' => 500],
            ['id' => $product->id, 'qty' => 2, 'price' => 999],
        ]]))->assertSessionHasNoErrors();

        $items = Order::firstOrFail()->items;
        $this->assertCount(1, $items);
        $this->assertSame(3, $items->first()->quantity);
        $this->assertSame(500.0, (float) $items->first()->unit_price);
        $this->assertSame(7, $product->fresh()->stock);
    }

    public function test_not_enough_stock_stops_the_order_and_leaves_the_stock_alone(): void
    {
        $plenty = Product::factory()->create(['stock' => 10]);
        $scarce = Product::factory()->create(['name' => 'কম স্টক', 'stock' => 1]);

        $this->actingAs($this->admin)->post(route('admin.orders.store'), $this->payload($plenty, ['items' => [
            ['id' => $plenty->id, 'qty' => 2, 'price' => 100],
            ['id' => $scarce->id, 'qty' => 5, 'price' => 100],
        ]]))->assertSessionHasErrors('items');

        $this->assertSame(0, Order::count());
        $this->assertSame(10, $plenty->fresh()->stock);
        $this->assertSame(1, $scarce->fresh()->stock);
    }

    public function test_the_order_can_start_as_pending(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.orders.store'), $this->payload($product, ['status' => 'pending']))->assertSessionHasNoErrors();
        $this->assertSame('pending', Order::firstOrFail()->status);

        $this->actingAs($this->admin)->post(route('admin.orders.store'), $this->payload($product, ['status' => 'delivered']))->assertSessionHasErrors('status');
    }

    public function test_meta_hears_about_the_sale_only_when_the_box_is_ticked(): void
    {
        $this->fakeMeta();
        $product = Product::factory()->create(['stock' => 10]);

        $this->actingAs($this->admin)->post(route('admin.orders.store'), $this->payload($product, ['send_to_meta' => '1']))->assertSessionHasNoErrors();

        $names = Http::recorded()->map(fn (array $pair) => $pair[0]->data()['data'][0]['event_name'] ?? null)->all();
        $this->assertContains('Purchase', $names);
        $this->assertContains('OrderConfirmed', $names);
        $this->assertSame('phone_call', Http::recorded()->first()[0]->data()['data'][0]['action_source']);

        $before = Http::recorded()->count();
        $this->actingAs($this->admin)->post(route('admin.orders.store'), $this->payload($product))->assertSessionHasNoErrors();
        $this->assertSame($before, Http::recorded()->count());
    }

    // ---------- the website must not be bendable ----------

    public function test_the_storefront_ignores_a_made_up_price_delivery_charge_or_source(): void
    {
        $product = Product::factory()->create(['price' => 15500, 'stock' => 10]);

        $this->postJson('/order', [
            'customer_name' => 'চালাক',
            'customer_phone' => '01805962220',
            'customer_address' => 'ঢাকা',
            'delivery_area' => 'inside',
            'delivery_charge' => 0,
            'source' => 'showroom',
            'items' => [['id' => $product->id, 'qty' => 1, 'price' => 1]],
        ])->assertOk();

        $order = Order::with('items')->firstOrFail();
        $this->assertSame('website', $order->source);
        $this->assertEqualsWithDelta(15500, (float) $order->subtotal, 0.001);
        $this->assertEqualsWithDelta(150, (float) $order->delivery_charge, 0.001);
    }

    // ---------- edit ----------

    private function orderWith(Product $product, int $qty = 3, string $status = 'confirmed'): Order
    {
        $product->decrement('stock', $qty);

        $order = Order::create([
            'order_number' => 'ARM-EDIT-'.random_int(1000, 9999),
            'customer_name' => 'পুরনো নাম',
            'customer_phone' => '01805962220',
            'customer_address' => 'পুরনো ঠিকানা',
            'delivery_area' => 'inside',
            'delivery_charge' => 150,
            'subtotal' => $qty * 1000,
            'grand_total' => $qty * 1000 + 150,
            'status' => $status,
        ]);
        $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 1000, 'quantity' => $qty, 'line_total' => $qty * 1000]);

        return $order;
    }

    public function test_edit_page_shows_the_current_details_and_counts_the_ordered_pieces_as_available(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 3);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.edit', $order))->assertOk();

        $response->assertSee('পুরনো নাম')->assertSee('পুরনো ঠিকানা');
        $config = $response->viewData('products');
        $this->assertSame(10, $config[0]['stock'], '7 left in stock plus the 3 held by this order');
        $this->assertSame([['id' => $product->id, 'qty' => 3, 'price' => 1000.0]], $response->viewData('startingLines'));
    }

    public function test_changing_the_quantity_moves_only_the_difference_in_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 3);

        $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $this->payload($product, [
            'customer_name' => 'নতুন নাম',
            'delivery_area' => 'outside',
            'delivery_charge' => 300,
            'items' => [['id' => $product->id, 'qty' => 5, 'price' => 900]],
        ]))->assertSessionHasNoErrors()->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame('নতুন নাম', $order->customer_name);
        $this->assertSame('outside', $order->delivery_area);
        $this->assertEqualsWithDelta(4500, (float) $order->subtotal, 0.001);
        $this->assertEqualsWithDelta(4800, (float) $order->grand_total, 0.001);
        $this->assertSame('confirmed', $order->status, 'editing does not change the status');
        $this->assertSame(5, $product->fresh()->stock, '10 - 3 earlier - 2 more');
        $this->assertCount(1, $order->items);

        $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $this->payload($product, ['items' => [['id' => $product->id, 'qty' => 1, 'price' => 900]]]))
            ->assertSessionHasNoErrors();
        $this->assertSame(9, $product->fresh()->stock);
    }

    public function test_removing_a_line_returns_its_stock_and_a_new_line_takes_its_own(): void
    {
        $old = Product::factory()->create(['stock' => 10]);
        $new = Product::factory()->create(['stock' => 4]);
        $order = $this->orderWith($old, 2);

        $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $this->payload($new, ['items' => [
            ['id' => $old->id, 'qty' => 0, 'price' => 1000],
            ['id' => $new->id, 'qty' => 3, 'price' => 2000],
        ]]))->assertSessionHasNoErrors();

        $this->assertSame(10, $old->fresh()->stock);
        $this->assertSame(1, $new->fresh()->stock);
        $this->assertSame([$new->id], $order->fresh()->items->pluck('product_id')->all());
    }

    public function test_an_edit_that_asks_for_too_much_changes_nothing(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 3);

        $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $this->payload($product, [
            'customer_name' => 'বদলাবে না',
            'items' => [['id' => $product->id, 'qty' => 11, 'price' => 1000]],
        ]))->assertSessionHasErrors('items');

        $this->assertSame(7, $product->fresh()->stock);
        $this->assertSame('পুরনো নাম', $order->fresh()->customer_name);
        $this->assertSame(3, $order->fresh()->items->first()->quantity);
    }

    public function test_delivered_and_cancelled_orders_cannot_be_edited(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        foreach (['delivered', 'cancelled'] as $status) {
            $order = $this->orderWith($product, 1, $status);

            $this->actingAs($this->admin)->get(route('admin.orders.edit', $order))->assertRedirect(route('admin.orders.show', $order))->assertSessionHas('error');
            $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $this->payload($product, ['customer_name' => 'বদলাবে না']))->assertSessionHasErrors('items');
            $this->assertSame('পুরনো নাম', $order->fresh()->customer_name);
        }
    }

    public function test_an_order_whose_product_was_deleted_cannot_be_edited(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 1);
        $product->delete();

        $this->actingAs($this->admin)->get(route('admin.orders.edit', $order))->assertRedirect(route('admin.orders.show', $order))->assertSessionHas('error');
        $this->actingAs($this->admin)->get(route('admin.orders.show', $order))->assertOk()->assertDontSee(route('admin.orders.edit', $order), false);
    }

    public function test_a_product_switched_off_after_the_order_may_stay_in_it(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 2);
        $product->update(['is_active' => false]);

        $edit = $this->actingAs($this->admin)->get(route('admin.orders.edit', $order))->assertOk();
        $this->assertStringContainsString('(বন্ধ আছে)', $edit->viewData('products')[0]['name']);

        $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $this->payload($product, ['items' => [['id' => $product->id, 'qty' => 3, 'price' => 1000]]]))
            ->assertSessionHasNoErrors();

        $this->assertSame(3, $order->fresh()->items->first()->quantity);
        $this->assertSame(7, $product->fresh()->stock);
    }

    // ---------- lists and details ----------

    public function test_order_list_and_details_show_the_source_and_the_edit_button(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 1);
        $order->update(['source' => 'facebook']);

        $this->actingAs($this->admin)->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee(route('admin.orders.create'), false)
            ->assertSee('ফেসবুক / মেসেঞ্জার');

        $this->actingAs($this->admin)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('উৎস: ফেসবুক / মেসেঞ্জার')
            ->assertSee(route('admin.orders.edit', $order), false);
    }

    public function test_website_orders_show_no_source_chip_in_the_list(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $this->orderWith($product, 1);

        $this->actingAs($this->admin)->get(route('admin.orders.index'))->assertOk()->assertDontSee('class="source-chip"', false);
    }
}
