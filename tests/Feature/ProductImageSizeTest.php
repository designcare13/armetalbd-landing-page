<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductController;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageSizeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(UploadedFile $image): array
    {
        return [
            'name' => 'Test Product',
            'price' => 5000,
            'stock' => 10,
            'rating' => 4.5,
            'reviews_count' => 3,
            'image' => $image,
        ];
    }

    private function banglaDigits(int $number): string
    {
        return strtr((string) $number, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
    }

    public function test_photos_smaller_than_the_minimum_on_either_side_are_rejected(): void
    {
        $min = ProductController::MIN_IMAGE_SIZE;
        $admin = User::factory()->create();

        foreach ([[$min - 1, $min], [$min, $min - 1], [500, 500]] as [$width, $height]) {
            $this->actingAs($admin)
                ->post(route('admin.products.store'), $this->productPayload(UploadedFile::fake()->image('small.jpg', $width, $height)))
                ->assertSessionHasErrors('image');
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_photos_at_or_above_the_minimum_are_accepted(): void
    {
        $min = ProductController::MIN_IMAGE_SIZE;
        $admin = User::factory()->create();

        foreach ([[$min, $min], [1200, 900], [900, 1600]] as $index => [$width, $height]) {
            $this->actingAs($admin)
                ->post(route('admin.products.store'), array_merge(
                    $this->productPayload(UploadedFile::fake()->image('ok.jpg', $width, $height)),
                    ['name' => "Product {$index}"],
                ))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('products', 3);
    }

    public function test_the_error_message_and_the_form_hint_state_the_real_minimum(): void
    {
        $size = $this->banglaDigits(ProductController::MIN_IMAGE_SIZE);
        $admin = User::factory()->create();

        $errors = $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload(UploadedFile::fake()->image('small.jpg', 300, 300)))
            ->assertSessionHasErrors('image')
            ->baseResponse->getSession()->get('errors')->get('image');

        $this->assertStringContainsString("{$size}×{$size}", $errors[0]);

        $this->actingAs($admin)->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee("কমপক্ষে {$size}×{$size} পিক্সেল")
            ->assertSee('var MIN_IMAGE_SIZE = '.ProductController::MIN_IMAGE_SIZE.';', false);
    }

    public function test_an_existing_product_with_a_small_photo_can_still_be_edited_without_a_new_photo(): void
    {
        $product = Product::factory()->create(['image' => 'products/old-small-photo.jpg', 'price' => 5000]);

        $this->actingAs(User::factory()->create())
            ->put(route('admin.products.update', $product), [
                'name' => 'নতুন নাম',
                'price' => 5500,
                'stock' => 10,
                'rating' => 4.5,
                'reviews_count' => 3,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('নতুন নাম', $product->fresh()->name);
        $this->assertSame('products/old-small-photo.jpg', $product->fresh()->image);
    }

    public function test_replacing_a_photo_with_a_small_one_is_rejected_and_the_old_photo_stays(): void
    {
        $product = Product::factory()->create(['image' => 'products/current.jpg']);

        $this->actingAs(User::factory()->create())
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => 5000,
                'stock' => 10,
                'rating' => 4.5,
                'reviews_count' => 3,
                'image' => UploadedFile::fake()->image('small.jpg', 600, 600),
            ])
            ->assertSessionHasErrors('image');

        $this->assertSame('products/current.jpg', $product->fresh()->image);
    }
}
