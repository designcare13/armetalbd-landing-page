<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListPaginationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function order(int $number, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => "ARM-LIST-{$number}",
            'customer_name' => "কাস্টমার {$number}",
            'customer_phone' => '01805962220',
            'customer_address' => 'মিরপুর, ঢাকা',
            'delivery_area' => 'inside',
            'delivery_charge' => 150,
            'subtotal' => 1000,
            'grand_total' => 1150,
            'status' => 'pending',
        ], $overrides));
    }

    public function test_footer_shows_the_total_and_the_page_size_choice_even_when_everything_fits_on_one_page(): void
    {
        $admin = User::factory()->create();
        Product::factory()->count(3)->create();
        $this->order(1);

        $this->actingAs($admin)->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('class="list-footer"', false)
            ->assertSee('মোট <strong>3</strong> টি', false)
            ->assertSee('name="per_page"', false);

        $this->actingAs($admin)->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('class="list-footer"', false)
            ->assertSee('মোট <strong>1</strong> টি', false);
    }

    public function test_page_size_can_be_chosen_and_unknown_sizes_fall_back_to_the_default(): void
    {
        $admin = User::factory()->create();
        Product::factory()->count(40)->create();

        $default = $this->actingAs($admin)->get(route('admin.products.index'));
        $this->assertCount(15, $default->viewData('products')->items());

        $thirty = $this->actingAs($admin)->get(route('admin.products.index', ['per_page' => 30]));
        $this->assertCount(30, $thirty->viewData('products')->items());
        $thirty->assertSee('per_page=30', false);

        $unknown = $this->actingAs($admin)->get(route('admin.products.index', ['per_page' => 100000]));
        $this->assertCount(15, $unknown->viewData('products')->items());

        $text = $this->actingAs($admin)->get(route('admin.products.index', ['per_page' => 'abc']));
        $this->assertCount(15, $text->viewData('products')->items());
    }

    public function test_orders_page_size_keeps_the_status_filter_and_search(): void
    {
        $admin = User::factory()->create();

        foreach (range(1, 35) as $number) {
            $this->order($number, ['customer_name' => $number <= 20 ? "রহিম {$number}" : "করিম {$number}"]);
        }

        $response = $this->actingAs($admin)->get(route('admin.orders.index', ['status' => 'pending', 'search' => 'রহিম', 'per_page' => 30]));

        $this->assertCount(20, $response->viewData('orders')->items());
        $response->assertSee('name="status" value="pending"', false)
            ->assertSee('name="search" value="রহিম"', false);

        $paged = $this->actingAs($admin)->get(route('admin.orders.index', ['per_page' => 30, 'page' => 2]));
        $this->assertCount(5, $paged->viewData('orders')->items());
    }

    public function test_orders_page_shows_summary_numbers_and_a_count_on_every_status_filter(): void
    {
        $admin = User::factory()->create();
        $this->order(1);
        $this->order(2);
        $this->order(3, ['status' => 'delivered', 'grand_total' => 4000]);
        $this->order(4, ['status' => 'delivered', 'grand_total' => 6000]);
        $this->order(5, ['status' => 'cancelled', 'grand_total' => 9999]);

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));

        $response->assertOk();
        $this->assertSame(5, $response->viewData('summary')['total']);
        $this->assertSame(2, $response->viewData('summary')['pending']);
        $this->assertSame(5, $response->viewData('summary')['today']);
        $this->assertSame(10000.0, $response->viewData('summary')['delivered_value']);
        $response->assertSee('৳10,000')
            ->assertSee('<span class="pill-count">2</span>', false)
            ->assertSee('<span class="pill-count">0</span>', false);
    }

    public function test_order_rows_show_the_customer_the_first_product_and_how_many_more(): void
    {
        $admin = User::factory()->create();
        $order = $this->order(1, ['customer_name' => 'রাসেল হোসাইন']);
        $order->items()->createMany([
            ['product_name' => 'দোলনা সুইং চেয়ার', 'unit_price' => 500, 'quantity' => 2, 'line_total' => 1000],
            ['product_name' => 'বেবি কট', 'unit_price' => 500, 'quantity' => 1, 'line_total' => 500],
        ]);

        $this->actingAs($admin)->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('রাসেল হোসাইন')
            ->assertSee('<div class="avatar">র</div>', false)
            ->assertSee('দোলনা সুইং চেয়ার')
            ->assertSee('আরও 1 টি প্রোডাক্ট')
            ->assertSee('ঢাকা সিটি');
    }

    public function test_orders_of_a_status_with_no_rows_show_the_empty_message(): void
    {
        $admin = User::factory()->create();
        $this->order(1);

        $this->actingAs($admin)->get(route('admin.orders.index', ['status' => 'shipped']))
            ->assertOk()
            ->assertSee('কোনো অর্ডার পাওয়া যায়নি।');
    }
}
