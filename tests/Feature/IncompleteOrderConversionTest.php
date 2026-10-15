<?php

namespace Tests\Feature;

use App\Models\IncompleteOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncompleteOrderConversionTest extends TestCase
{
    use RefreshDatabase;

    private function entryFor(Product $product, int $qty = 1, array $overrides = []): IncompleteOrder
    {
        return IncompleteOrder::factory()->create(array_merge([
            'customer_phone' => '01712345678',
            'customer_name' => 'রহিম উদ্দিন',
            'customer_address' => 'মিরপুর-১০, ঢাকা',
            'delivery_area' => 'inside',
            'items' => [['product_id' => $product->id, 'name' => $product->name, 'price' => (float) $product->price, 'qty' => $qty]],
            'cart_total' => $product->price * $qty,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function convertPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'রহিম উদ্দিন',
            'customer_phone' => '01712345678',
            'customer_address' => 'মিরপুর-১০, ঢাকা',
            'delivery_area' => 'inside',
            'order_note' => 'বিকেলে ডেলিভারি দিন',
            'items' => [['id' => $product->id, 'qty' => 2]],
        ], $overrides);
    }

    public function test_guests_cannot_convert_an_entry(): void
    {
        $product = Product::factory()->create();
        $entry = $this->entryFor($product);

        $this->get(route('admin.incomplete-orders.convert', $entry))->assertRedirect('/login');
        $this->post(route('admin.incomplete-orders.convert.store', $entry), $this->convertPayload($product))->assertRedirect('/login');

        $this->assertDatabaseCount('orders', 0);
        $this->assertModelExists($entry);
    }

    public function test_list_page_offers_the_convert_button(): void
    {
        $entry = $this->entryFor(Product::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('admin.incomplete-orders.index'))
            ->assertOk()
            ->assertSee(route('admin.incomplete-orders.convert', $entry), false);
    }

    public function test_form_is_prefilled_and_shows_todays_price_and_stock(): void
    {
        $product = Product::factory()->create(['name' => 'সুইং চেয়ার', 'price' => 16000, 'stock' => 7]);
        $entry = $this->entryFor($product, 2, ['items' => [['product_id' => $product->id, 'name' => 'সুইং চেয়ার', 'price' => 15000.0, 'qty' => 2]]]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.incomplete-orders.convert', $entry))
            ->assertOk()
            ->assertSee('value="রহিম উদ্দিন"', false)
            ->assertSee('value="01712345678"', false)
            ->assertSee('সুইং চেয়ার')
            ->assertSee('৳16,000')
            ->assertSee('data-price="16000"', false)
            ->assertSee('max="7"', false);
    }

    public function test_form_marks_products_that_can_no_longer_be_ordered(): void
    {
        $soldOut = Product::factory()->create(['stock' => 0]);
        $inactive = Product::factory()->create(['is_active' => false]);
        $entry = $this->entryFor($soldOut, 1, ['items' => [
            ['product_id' => $soldOut->id, 'name' => $soldOut->name, 'price' => 1000.0, 'qty' => 1],
            ['product_id' => $inactive->id, 'name' => $inactive->name, 'price' => 1000.0, 'qty' => 1],
            ['product_id' => 987654, 'name' => 'মুছে ফেলা পণ্য', 'price' => 1000.0, 'qty' => 1],
        ]]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.incomplete-orders.convert', $entry))
            ->assertOk()
            ->assertSee('স্টক শেষ')
            ->assertSee('প্রোডাক্টটি এখন বন্ধ আছে')
            ->assertSee('প্রোডাক্টটি আর নেই')
            ->assertSee('অর্ডার তৈরি করা যাচ্ছে না');
    }

    public function test_form_warns_when_the_number_already_has_an_order(): void
    {
        $product = Product::factory()->create();
        $entry = $this->entryFor($product);
        $existing = Order::place($this->convertPayload($product), [['id' => $product->id, 'qty' => 1]]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.incomplete-orders.convert', $entry))
            ->assertOk()
            ->assertSee('এই নম্বরে আগেও অর্ডার আছে')
            ->assertSee($existing->order_number);
    }

    public function test_converting_creates_a_confirmed_order_takes_stock_and_removes_the_entry(): void
    {
        Setting::set('delivery_inside', '120');
        $product = Product::factory()->create(['price' => 15500, 'stock' => 5]);
        $entry = $this->entryFor($product);
        $sameNumberElsewhere = IncompleteOrder::factory()->create(['customer_phone' => '01712345678']);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('admin.incomplete-orders.convert.store', $entry), $this->convertPayload($product));

        $order = Order::firstOrFail();
        $response->assertRedirect(route('admin.orders.show', $order))->assertSessionHas('success');

        $this->assertSame('confirmed', $order->status);
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('রহিম উদ্দিন', $order->customer_name);
        $this->assertSame('বিকেলে ডেলিভারি দিন', $order->order_note);
        $this->assertEquals(31000, $order->subtotal);
        $this->assertEquals(120, $order->delivery_charge);
        $this->assertEquals(31120, $order->grand_total);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(3, $product->fresh()->stock);

        $this->assertModelMissing($entry);
        $this->assertModelMissing($sameNumberElsewhere);
    }

    public function test_the_admin_can_fix_the_phone_number_and_the_original_entry_is_still_removed(): void
    {
        $product = Product::factory()->create();
        $entry = $this->entryFor($product);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.incomplete-orders.convert.store', $entry), $this->convertPayload($product, ['customer_phone' => '০১৮১২৩৪৫৬৭৮']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', ['customer_phone' => '01812345678']);
        $this->assertModelMissing($entry);
    }

    public function test_lines_with_zero_quantity_are_left_out_of_the_order(): void
    {
        $kept = Product::factory()->create(['stock' => 5]);
        $dropped = Product::factory()->create(['stock' => 5]);
        $entry = $this->entryFor($kept);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.incomplete-orders.convert.store', $entry), $this->convertPayload($kept, [
                'items' => [['id' => $kept->id, 'qty' => 1], ['id' => $dropped->id, 'qty' => 0]],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('order_items', 1);
        $this->assertSame(5, $dropped->fresh()->stock);
    }

    public function test_missing_customer_details_are_rejected_and_nothing_changes(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $entry = $this->entryFor($product, 1, ['customer_name' => null, 'customer_address' => null]);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.incomplete-orders.convert.store', $entry), $this->convertPayload($product, ['customer_name' => '', 'customer_address' => '']))
            ->assertSessionHasErrors(['customer_name', 'customer_address']);

        $this->actingAs($admin)
            ->post(route('admin.incomplete-orders.convert.store', $entry), $this->convertPayload($product, ['customer_phone' => '0171']))
            ->assertSessionHasErrors('customer_phone');

        $this->assertDatabaseCount('orders', 0);
        $this->assertModelExists($entry);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_all_quantities_zero_is_rejected(): void
    {
        $product = Product::factory()->create();
        $entry = $this->entryFor($product);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.incomplete-orders.convert.store', $entry), $this->convertPayload($product, ['items' => [['id' => $product->id, 'qty' => 0]]]))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertModelExists($entry);
    }

    public function test_insufficient_stock_is_reported_and_the_entry_is_kept(): void
    {
        $product = Product::factory()->create(['name' => 'সুইং চেয়ার', 'stock' => 1]);
        $entry = $this->entryFor($product);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.incomplete-orders.convert.store', $entry), $this->convertPayload($product))
            ->assertSessionHasErrors(['items' => "দুঃখিত, 'সুইং চেয়ার' এর মাত্র 1 টি স্টকে আছে।"]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertModelExists($entry);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_an_inactive_product_cannot_be_ordered_through_conversion(): void
    {
        $product = Product::factory()->create(['is_active' => false, 'stock' => 5]);
        $entry = $this->entryFor($product);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.incomplete-orders.convert.store', $entry), $this->convertPayload($product))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertModelExists($entry);
    }
}
