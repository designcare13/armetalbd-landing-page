<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductQuickViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_passes_the_full_description_to_the_popup(): void
    {
        $withDescription = Product::factory()->create(['description' => "প্রথম লাইন\nদ্বিতীয় লাইন"]);
        $withoutDescription = Product::factory()->create(['description' => null]);

        $products = $this->get('/')->assertOk()->viewData('productsFormatted')->keyBy('id');

        $this->assertSame("প্রথম লাইন\nদ্বিতীয় লাইন", $products[$withDescription->id]['description']);
        $this->assertNull($products[$withoutDescription->id]['description']);
    }

    public function test_landing_page_renders_the_popup_shell_and_phone_for_the_call_button(): void
    {
        Setting::set('phone', '01712345678');

        $this->get('/')
            ->assertOk()
            ->assertSee('class="pv-dialog"', false)
            ->assertSee('id="quickViewContent"', false)
            ->assertSee('phone: "01712345678"', false);
    }

    public function test_landing_assets_are_versioned_so_browsers_do_not_keep_stale_copies(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('css/style.css?v='.filemtime(public_path('css/style.css')), false)
            ->assertSee('js/landing.js?v='.filemtime(public_path('js/landing.js')), false);
    }

    public function test_admin_can_save_and_see_a_detailed_description(): void
    {
        $product = Product::factory()->create();
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $this->validProductPayload($product, [
                'description' => "প্রথম লাইন\nদ্বিতীয় লাইন",
            ]))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame("প্রথম লাইন\nদ্বিতীয় লাইন", $product->fresh()->description);

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('name="description"', false)
            ->assertSee('প্রথম লাইন');
    }

    public function test_detailed_description_is_optional_and_limited_in_length(): void
    {
        $product = Product::factory()->create(['description' => 'পুরনো বিবরণ']);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $this->validProductPayload($product, ['description' => '']))
            ->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->description);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $this->validProductPayload($product, [
                'description' => str_repeat('ক', 5001),
            ]))
            ->assertSessionHasErrors('description');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validProductPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'name' => $product->name,
            'price' => 15500,
            'stock' => 10,
            'rating' => 5,
            'reviews_count' => 25,
        ], $overrides);
    }
}
