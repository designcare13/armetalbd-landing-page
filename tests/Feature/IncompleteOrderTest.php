<?php

namespace Tests\Feature;

use App\Models\IncompleteOrder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncompleteOrderTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '3f1c2c9e-7a5b-4e0e-9a43-0d6f2b1c8a11';

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function capturePayload(array $overrides = []): array
    {
        return array_merge([
            'visitor_token' => self::TOKEN,
            'customer_phone' => '01712345678',
            'customer_name' => 'রহিম উদ্দিন',
            'customer_address' => 'মিরপুর-১০, ঢাকা',
            'delivery_area' => 'inside',
            'items' => [],
        ], $overrides);
    }

    // ---------- capture endpoint ----------

    public function test_a_valid_phone_number_saves_the_details_with_prices_taken_from_the_database(): void
    {
        $product = Product::factory()->create(['name' => 'সুইং চেয়ার', 'price' => 15500]);

        $this->postJson(route('landing.incomplete'), $this->capturePayload([
            'items' => [['id' => $product->id, 'qty' => 2, 'price' => 1, 'name' => 'জাল নাম']],
        ]))->assertNoContent();

        $saved = IncompleteOrder::firstOrFail();
        $this->assertSame('01712345678', $saved->customer_phone);
        $this->assertSame('রহিম উদ্দিন', $saved->customer_name);
        $this->assertSame('new', $saved->status);
        $this->assertEquals([['product_id' => $product->id, 'name' => 'সুইং চেয়ার', 'price' => 15500.0, 'qty' => 2]], $saved->items);
        $this->assertEquals(31000, $saved->cart_total);
    }

    public function test_phone_numbers_typed_with_bangla_digits_are_accepted_and_normalized(): void
    {
        $this->postJson(route('landing.incomplete'), $this->capturePayload(['customer_phone' => '+৮৮০ ১৭১২-৩৪৫৬৭৮']))
            ->assertNoContent();

        $this->assertDatabaseHas('incomplete_orders', ['customer_phone' => '01712345678']);
    }

    public function test_the_same_browser_updates_one_row_instead_of_adding_more(): void
    {
        $this->postJson(route('landing.incomplete'), $this->capturePayload(['customer_name' => null]))->assertNoContent();
        $this->postJson(route('landing.incomplete'), $this->capturePayload(['customer_name' => 'করিম']))->assertNoContent();

        $this->assertDatabaseCount('incomplete_orders', 1);
        $this->assertSame('করিম', IncompleteOrder::first()->customer_name);
    }

    public function test_admin_follow_up_status_survives_when_the_visitor_keeps_typing(): void
    {
        IncompleteOrder::factory()->create(['visitor_token' => self::TOKEN, 'status' => 'call_later']);

        $this->postJson(route('landing.incomplete'), $this->capturePayload(['customer_name' => 'নতুন নাম']))->assertNoContent();

        $saved = IncompleteOrder::firstOrFail();
        $this->assertSame('call_later', $saved->status);
        $this->assertSame('নতুন নাম', $saved->customer_name);
    }

    public function test_invalid_input_is_rejected_and_nothing_is_stored(): void
    {
        $this->postJson(route('landing.incomplete'), $this->capturePayload(['customer_phone' => '0171234']))
            ->assertJsonValidationErrors('customer_phone');
        $this->postJson(route('landing.incomplete'), $this->capturePayload(['visitor_token' => 'not-a-uuid']))
            ->assertJsonValidationErrors('visitor_token');
        $this->postJson(route('landing.incomplete'), $this->capturePayload(['delivery_area' => 'mars']))
            ->assertJsonValidationErrors('delivery_area');
        $this->postJson(route('landing.incomplete'), $this->capturePayload(['items' => [['id' => 1, 'qty' => 999]]]))
            ->assertJsonValidationErrors('items.0.qty');

        $this->assertDatabaseCount('incomplete_orders', 0);
    }

    public function test_inactive_and_unknown_products_are_left_out_of_the_saved_cart(): void
    {
        $active = Product::factory()->create(['price' => 1000]);
        $inactive = Product::factory()->create(['price' => 2000, 'is_active' => false]);

        $this->postJson(route('landing.incomplete'), $this->capturePayload([
            'items' => [
                ['id' => $active->id, 'qty' => 1],
                ['id' => $inactive->id, 'qty' => 1],
                ['id' => 987654, 'qty' => 1],
            ],
        ]))->assertNoContent();

        $saved = IncompleteOrder::firstOrFail();
        $this->assertCount(1, $saved->items);
        $this->assertEquals(1000, $saved->cart_total);
    }

    // ---------- relation to real orders ----------

    private function placeOrder(Product $product, string $phone = '01712345678'): void
    {
        $this->postJson(route('landing.order'), [
            'customer_name' => 'রহিম উদ্দিন',
            'customer_phone' => $phone,
            'customer_address' => 'মিরপুর-১০, ঢাকা',
            'delivery_area' => 'inside',
            'visitor_token' => self::TOKEN,
            'items' => [['id' => $product->id, 'qty' => 1]],
        ])->assertOk()->assertJson(['success' => true]);
    }

    public function test_placing_an_order_removes_the_visitors_incomplete_entries(): void
    {
        $product = Product::factory()->create();
        $sameBrowser = IncompleteOrder::factory()->create(['visitor_token' => self::TOKEN, 'customer_phone' => '01800000000']);
        $samePhoneOtherDevice = IncompleteOrder::factory()->create(['customer_phone' => '01712345678']);
        $unrelated = IncompleteOrder::factory()->create(['customer_phone' => '01999999999']);

        $this->placeOrder($product);

        $this->assertModelMissing($sameBrowser);
        $this->assertModelMissing($samePhoneOtherDevice);
        $this->assertModelExists($unrelated);
    }

    public function test_a_late_capture_request_does_not_bring_back_an_entry_after_the_order(): void
    {
        $product = Product::factory()->create();
        $this->placeOrder($product);

        $this->postJson(route('landing.incomplete'), $this->capturePayload())->assertNoContent();

        $this->assertDatabaseCount('incomplete_orders', 0);
    }

    // ---------- retention ----------

    public function test_entries_older_than_the_retention_period_are_pruned(): void
    {
        $fresh = IncompleteOrder::factory()->create();
        $stale = IncompleteOrder::factory()->expired()->create();

        $this->assertSame(1, IncompleteOrder::pruneExpired());

        $this->assertModelExists($fresh);
        $this->assertModelMissing($stale);
    }

    // ---------- admin ----------

    public function test_guests_cannot_reach_the_admin_pages(): void
    {
        $entry = IncompleteOrder::factory()->create();

        $this->get(route('admin.incomplete-orders.index'))->assertRedirect('/login');
        $this->patch(route('admin.incomplete-orders.status', $entry), ['status' => 'no_answer'])->assertRedirect('/login');
        $this->delete(route('admin.incomplete-orders.destroy', $entry))->assertRedirect('/login');

        $this->assertModelExists($entry);
        $this->assertSame('new', $entry->fresh()->status);
    }

    public function test_admin_sees_entries_with_call_and_whatsapp_links_and_can_filter_and_search(): void
    {
        IncompleteOrder::factory()->create(['customer_name' => 'আলমগীর', 'customer_phone' => '01711111111', 'status' => 'new']);
        IncompleteOrder::factory()->create(['customer_name' => 'বেলাল', 'customer_phone' => '01822222222', 'status' => 'no_answer']);
        IncompleteOrder::factory()->expired()->create(['customer_name' => 'পুরনো জন']);
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.incomplete-orders.index'))
            ->assertOk()
            ->assertSee('আলমগীর')
            ->assertSee('বেলাল')
            ->assertSee('tel:01711111111', false)
            ->assertSee('https://wa.me/8801711111111', false);

        $this->actingAs($admin)->get(route('admin.incomplete-orders.index', ['status' => 'no_answer']))
            ->assertSee('বেলাল')
            ->assertDontSee('আলমগীর');

        $this->actingAs($admin)->get(route('admin.incomplete-orders.index', ['search' => '01711']))
            ->assertSee('আলমগীর')
            ->assertDontSee('বেলাল');

        $this->assertDatabaseMissing('incomplete_orders', ['customer_name' => 'পুরনো জন']);
    }

    public function test_admin_can_update_the_follow_up_status_but_only_to_a_known_one(): void
    {
        $entry = IncompleteOrder::factory()->create();
        $admin = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.incomplete-orders.status', $entry), ['status' => 'call_later'])
            ->assertSessionHas('success');
        $this->assertSame('call_later', $entry->fresh()->status);

        $this->actingAs($admin)->patch(route('admin.incomplete-orders.status', $entry), ['status' => 'bogus'])
            ->assertSessionHasErrors('status');
        $this->assertSame('call_later', $entry->fresh()->status);
    }

    public function test_admin_can_delete_an_entry(): void
    {
        $entry = IncompleteOrder::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.incomplete-orders.destroy', $entry))
            ->assertSessionHas('success');

        $this->assertModelMissing($entry);
    }

    public function test_sidebar_shows_how_many_new_entries_need_a_call(): void
    {
        IncompleteOrder::factory()->count(3)->create(['status' => 'new']);
        IncompleteOrder::factory()->create(['status' => 'no_answer']);
        IncompleteOrder::factory()->expired()->create(['status' => 'new']);

        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<span class="badge-count">3</span>', false);
    }

    // ---------- landing page ----------

    public function test_landing_page_tells_visitors_their_details_are_saved_and_exposes_the_endpoint(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('form-privacy-note', false)
            ->assertSee("incompleteUrl: '".route('landing.incomplete')."'", false);
    }
}
