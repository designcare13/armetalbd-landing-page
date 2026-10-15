<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_shows_the_stock_of_every_product_with_a_warning_when_it_runs_low_or_out(): void
    {
        $admin = User::factory()->create();
        Product::factory()->create(['name' => 'ভরা স্টক', 'stock' => 40]);
        Product::factory()->create(['name' => 'কম স্টক', 'stock' => Product::LOW_STOCK_LIMIT]);
        Product::factory()->create(['name' => 'শেষ স্টক', 'stock' => 0]);

        $this->actingAs($admin)->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('<th>স্টক</th>', false)
            ->assertSee('<span class="stock-badge stock-ok">40 টি</span>', false)
            ->assertSee('stock-badge stock-low', false)
            ->assertSee('stock-badge stock-out', false)
            ->assertSee('স্টক শেষ');
    }

    public function test_search_finds_products_by_name(): void
    {
        $admin = User::factory()->create();
        Product::factory()->create(['name' => 'দোলনা সুইং চেয়ার']);
        Product::factory()->create(['name' => 'কিচেন ট্রলি']);

        $response = $this->actingAs($admin)->get(route('admin.products.index', ['search' => 'দোলনা']));

        $response->assertOk()->assertSee('খোঁজার ফলাফল')->assertSee('দোলনা সুইং চেয়ার')->assertDontSee('কিচেন ট্রলি');
        $this->assertSame(1, $response->viewData('products')->total());
    }

    public function test_search_also_matches_the_category_name(): void
    {
        $admin = User::factory()->create();
        $beds = Category::create(['name' => 'মেটাল বেড', 'slug' => 'metal-bed', 'sort_order' => 1, 'is_active' => true]);
        Product::factory()->create(['name' => 'ক্যানোপি ফ্রেম', 'category_id' => $beds->id]);
        Product::factory()->create(['name' => 'অন্য জিনিস']);

        $response = $this->actingAs($admin)->get(route('admin.products.index', ['search' => 'মেটাল বেড']));

        $this->assertSame(['ক্যানোপি ফ্রেম'], $response->viewData('products')->pluck('name')->all());
    }

    public function test_search_with_no_match_shows_the_empty_message_and_a_way_to_clear_it(): void
    {
        $admin = User::factory()->create();
        Product::factory()->create(['name' => 'দোলনা']);

        $this->actingAs($admin)->get(route('admin.products.index', ['search' => 'কিছুই নেই']))
            ->assertOk()
            ->assertSee('কোনো প্রোডাক্ট পাওয়া যায়নি।')
            ->assertSee('title="ক্লিয়ার করুন"', false);
    }

    public function test_search_survives_paging_and_the_page_size_choice(): void
    {
        $admin = User::factory()->create();
        Product::factory()->count(20)->sequence(fn ($sequence) => ['name' => 'দোলনা '.$sequence->index])->create();
        Product::factory()->count(5)->create(['name' => 'অন্য']);

        $paged = $this->actingAs($admin)->get(route('admin.products.index', ['search' => 'দোলনা']));
        $paged->assertSee('search=%E0%A6%A6%E0%A7%8B%E0%A6%B2%E0%A6%A8%E0%A6%BE', false)->assertSee('page=2', false);
        $this->assertSame(20, $paged->viewData('products')->total());

        $this->actingAs($admin)->get(route('admin.products.index', ['search' => 'দোলনা', 'per_page' => 30]))
            ->assertSee('name="search" value="দোলনা"', false)
            ->assertSee('<input type="hidden" name="search" value="দোলনা">', false);
    }
}
