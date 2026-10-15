<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductDiscountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: float, 1: float, 2: int, 3: string}>
     */
    public static function automaticDiscountProvider(): array
    {
        return [
            'rounds down 13.89 percent' => [15500, 18000, 13, '১৩% ছাড়'],
            'matches seeded 17 percent' => [13650, 16500, 17, '১৭% ছাড়'],
            'rounds down 22.5 percent' => [15500, 20000, 22, '২২% ছাড়'],
            'exact 25 percent' => [12000, 16000, 25, '২৫% ছাড়'],
            'handles paisa prices' => [98.99, 100, 1, '১% ছাড়'],
        ];
    }

    #[DataProvider('automaticDiscountProvider')]
    public function test_discount_is_calculated_from_prices_and_rounded_down(float $price, float $oldPrice, int $percent, string $label): void
    {
        $product = Product::factory()->make(['price' => $price, 'old_price' => $oldPrice]);

        $this->assertSame($percent, $product->discount_percent);
        $this->assertSame($label, $product->discount_label);
    }

    /**
     * @return array<string, array{0: float, 1: float|null}>
     */
    public static function noDiscountProvider(): array
    {
        return [
            'no old price' => [15500, null],
            'old price equals price' => [15500, 15500],
            'old price lower than price' => [15500, 12000],
            'discount below one percent' => [99.99, 100.05],
        ];
    }

    #[DataProvider('noDiscountProvider')]
    public function test_no_discount_is_shown_without_a_real_price_reduction(float $price, ?float $oldPrice): void
    {
        $product = Product::factory()->make(['price' => $price, 'old_price' => $oldPrice]);

        $this->assertNull($product->discount_percent);
        $this->assertNull($product->discount_label);
    }

    public function test_custom_discount_text_overrides_the_automatic_percentage(): void
    {
        $product = Product::factory()->make([
            'price' => 15500,
            'old_price' => 18000,
            'discount_text' => 'ঈদ অফার',
        ]);

        $this->assertSame('ঈদ অফার', $product->discount_label);
        $this->assertSame(13, $product->discount_percent);
    }

    public function test_custom_discount_text_is_shown_even_without_old_price(): void
    {
        $product = Product::factory()->make(['price' => 15500, 'discount_text' => 'বিশেষ অফার']);

        $this->assertSame('বিশেষ অফার', $product->discount_label);
    }

    public function test_landing_page_receives_the_automatic_discount_label(): void
    {
        $automatic = Product::factory()->create(['price' => 15500, 'old_price' => 18000]);
        $custom = Product::factory()->create(['price' => 15500, 'old_price' => 18000, 'discount_text' => 'ঈদ অফার']);
        $noDiscount = Product::factory()->create(['price' => 15500]);

        $products = $this->get('/')->assertOk()->viewData('productsFormatted')->keyBy('id');

        $this->assertSame('১৩% ছাড়', $products[$automatic->id]['discount']);
        $this->assertSame('ঈদ অফার', $products[$custom->id]['discount']);
        $this->assertNull($products[$noDiscount->id]['discount']);
    }

    public function test_admin_edit_form_previews_the_automatic_discount(): void
    {
        $product = Product::factory()->create(['price' => 15500, 'old_price' => 18000]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('অটো: ১৩% ছাড়');
    }

    public function test_admin_edit_form_preview_ignores_the_custom_discount_text(): void
    {
        $product = Product::factory()->create(['price' => 15500, 'old_price' => 18000, 'discount_text' => 'ঈদ অফার']);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('অটো: ১৩% ছাড়')
            ->assertDontSee('অটো: ঈদ অফার');
    }

    public function test_admin_cannot_save_an_old_price_lower_than_the_current_price(): void
    {
        $product = Product::factory()->create(['price' => 15500]);

        $this->actingAs(User::factory()->create())
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => 15500,
                'old_price' => 12000,
                'stock' => 10,
                'rating' => 5,
                'reviews_count' => 25,
            ])
            ->assertSessionHasErrors('old_price');

        $this->assertNull($product->fresh()->old_price);
    }
}
