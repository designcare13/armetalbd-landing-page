<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductController;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageSizeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploads');
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(UploadedFile $image, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Product',
            'price' => 5000,
            'stock' => 10,
            'rating' => 4.5,
            'reviews_count' => 3,
            'image' => $image,
        ], $overrides);
    }

    private function banglaDigits(int $number): string
    {
        return strtr((string) $number, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
    }

    /**
     * A photo-like JPEG (random blocks, so it compresses like a real picture).
     */
    private function photo(int $width, int $height, ?int $exifOrientation = null): File
    {
        mt_srand(7);
        $canvas = imagecreatetruecolor($width, $height);

        for ($i = 0; $i < 400; $i++) {
            $color = imagecolorallocate($canvas, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            $x = mt_rand(0, $width);
            $y = mt_rand(0, $height);
            imagefilledrectangle($canvas, $x, $y, $x + mt_rand(20, 400), $y + mt_rand(20, 400), $color);
        }

        ob_start();
        imagejpeg($canvas, null, 90);
        $jpeg = (string) ob_get_clean();

        if ($exifOrientation !== null) {
            $tiff = "II*\x00".pack('V', 8).pack('v', 1).pack('vvVV', 0x0112, 3, 1, $exifOrientation).pack('V', 0);
            $app1 = "Exif\x00\x00".$tiff;
            $jpeg = substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($app1) + 2).$app1.substr($jpeg, 2);
        }

        return UploadedFile::fake()->createWithContent('photo.jpg', $jpeg);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function storedSize(Product $product): array
    {
        $size = getimagesize(Storage::disk('uploads')->path($product->image));

        return [$size[0], $size[1]];
    }

    // ---------- minimum size: tiny is blocked, small is only a warning in the form ----------

    public function test_photos_below_the_hard_minimum_are_rejected(): void
    {
        $min = ProductController::MIN_IMAGE_SIZE;
        $admin = User::factory()->create();

        foreach ([[$min - 1, $min], [$min, $min - 1], [100, 100]] as [$width, $height]) {
            $this->actingAs($admin)
                ->post(route('admin.products.store'), $this->productPayload(UploadedFile::fake()->image('tiny.jpg', $width, $height)))
                ->assertSessionHasErrors('image');
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_small_but_usable_photos_are_accepted(): void
    {
        $admin = User::factory()->create();

        foreach ([[300, 300], [500, 500], [799, 800], [800, 800], [1200, 900]] as $index => [$width, $height]) {
            $this->actingAs($admin)
                ->post(route('admin.products.store'), $this->productPayload(UploadedFile::fake()->image('ok.jpg', $width, $height), ['name' => "Product {$index}"]))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('products', 5);
    }

    public function test_the_form_texts_and_script_limits_match_the_rules(): void
    {
        $min = $this->banglaDigits(ProductController::MIN_IMAGE_SIZE);
        $good = $this->banglaDigits(ProductController::GOOD_IMAGE_SIZE);
        $admin = User::factory()->create();

        $errors = $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload(UploadedFile::fake()->image('tiny.jpg', 100, 100)))
            ->assertSessionHasErrors('image')
            ->baseResponse->getSession()->get('errors')->get('image');

        $this->assertStringContainsString("{$min}×{$min}", $errors[0]);

        $this->actingAs($admin)->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee("কমপক্ষে {$min}×{$min} পিক্সেল")
            ->assertSee("{$good}×{$good} বা বড়")
            ->assertSee('সর্বোচ্চ ১০ MB')
            ->assertSee('var MIN_IMAGE_SIZE = '.ProductController::MIN_IMAGE_SIZE.';', false)
            ->assertSee('var GOOD_IMAGE_SIZE = '.ProductController::GOOD_IMAGE_SIZE.';', false)
            ->assertSee('var MAX_UPLOAD_MB = 10;', false);
    }

    public function test_the_upload_limit_is_ten_megabytes(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload(UploadedFile::fake()->image('big.jpg', 800, 800)->size(9000)))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload(UploadedFile::fake()->image('huge.jpg', 800, 800)->size(11000), ['name' => 'Second']))
            ->assertSessionHasErrors('image');
    }

    // ---------- big photos are scaled down when saved ----------

    public function test_a_large_photo_is_scaled_down_keeping_its_proportions(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.products.store'), $this->productPayload($this->photo(3200, 2400)))
            ->assertSessionHasNoErrors();

        $product = Product::firstOrFail();
        $max = ProductController::STORED_IMAGE_MAX_SIDE;

        $this->assertSame([$max, 1200], $this->storedSize($product));
        $this->assertStringEndsWith('.jpg', $product->image);
    }

    public function test_a_photo_that_is_already_small_enough_is_stored_untouched(): void
    {
        $photo = $this->photo(1200, 900);
        $original = file_get_contents($photo->getRealPath());

        $this->actingAs(User::factory()->create())
            ->post(route('admin.products.store'), $this->productPayload($photo))
            ->assertSessionHasNoErrors();

        $this->assertSame($original, Storage::disk('uploads')->get(Product::firstOrFail()->image));
    }

    public function test_phone_photos_keep_their_upright_orientation_after_scaling(): void
    {
        if (! function_exists('exif_read_data')) {
            $this->markTestSkipped('The exif extension is not available.');
        }

        // EXIF orientation 6 means "rotate 90 degrees clockwise": a landscape file that is shown as a portrait photo.
        $this->actingAs(User::factory()->create())
            ->post(route('admin.products.store'), $this->productPayload($this->photo(3200, 2400, exifOrientation: 6)))
            ->assertSessionHasNoErrors();

        $this->assertSame([1200, ProductController::STORED_IMAGE_MAX_SIDE], $this->storedSize(Product::firstOrFail()));
    }

    public function test_png_transparency_survives_scaling(): void
    {
        $canvas = imagecreatetruecolor(1800, 1350);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        mt_srand(3);
        for ($i = 0; $i < 300; $i++) {
            $x = mt_rand(500, 1300);
            $y = mt_rand(300, 1000);
            imagefilledrectangle($canvas, $x, $y, $x + 80, $y + 80, imagecolorallocatealpha($canvas, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), 0));
        }
        ob_start();
        imagepng($canvas, null, 0); // uncompressed, so the scaled copy is clearly lighter
        $png = UploadedFile::fake()->createWithContent('logo.png', (string) ob_get_clean());

        $this->actingAs(User::factory()->create())
            ->post(route('admin.products.store'), $this->productPayload($png))
            ->assertSessionHasNoErrors();

        $product = Product::firstOrFail();
        $stored = imagecreatefrompng(Storage::disk('uploads')->path($product->image));

        $this->assertStringEndsWith('.png', $product->image);
        $this->assertSame([ProductController::STORED_IMAGE_MAX_SIDE, 1200], $this->storedSize($product));
        $this->assertSame(127, (imagecolorat($stored, 2, 2) >> 24) & 0x7F, 'the empty corner must stay transparent');
    }

    // ---------- editing ----------

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

    public function test_replacing_a_photo_with_a_tiny_one_is_rejected_and_the_old_photo_stays(): void
    {
        $product = Product::factory()->create(['image' => 'products/current.jpg']);

        $this->actingAs(User::factory()->create())
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => 5000,
                'stock' => 10,
                'rating' => 4.5,
                'reviews_count' => 3,
                'image' => UploadedFile::fake()->image('tiny.jpg', 200, 200),
            ])
            ->assertSessionHasErrors('image');

        $this->assertSame('products/current.jpg', $product->fresh()->image);
    }
}
