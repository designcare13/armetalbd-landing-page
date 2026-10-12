<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Faq;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('uploads');
    }

    private function admin(): User
    {
        return User::first();
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Product',
            'category_id' => Category::first()->id,
            'price' => 5000,
            'stock' => 10,
            'rating' => 4.5,
            'reviews_count' => 3,
            'image' => UploadedFile::fake()->image('chair.jpg', 600, 600),
        ], $overrides);
    }

    public function test_admin_can_create_edit_and_delete_category(): void
    {
        $this->actingAs($this->admin())->get('/admin/categories')->assertOk()->assertSee('data-confirm=', false);

        $this->actingAs($this->admin())->post('/admin/categories', [
            'name' => 'সোফা ও চেয়ার',
            'sort_order' => 9,
            'is_active' => 1,
        ])->assertRedirect('/admin/categories');

        $category = Category::where('name', 'সোফা ও চেয়ার')->firstOrFail();
        $this->assertNotSame('', $category->slug);

        $this->actingAs($this->admin())->put("/admin/categories/{$category->id}", [
            'name' => 'সোফা',
            'sort_order' => 3,
        ])->assertRedirect('/admin/categories');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'সোফা', 'is_active' => 0]);

        $this->actingAs($this->admin())->get("/admin/categories/{$category->id}/edit")->assertOk();

        $this->actingAs($this->admin())->delete("/admin/categories/{$category->id}")->assertRedirect('/admin/categories');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_validation_rejects_bad_input(): void
    {
        $this->actingAs($this->admin())->post('/admin/categories', ['name' => ''])
            ->assertSessionHasErrors(['name']);
    }

    public function test_deleting_category_keeps_its_products(): void
    {
        $category = Category::first();
        $product = Product::where('category_id', $category->id)->first();

        $this->actingAs($this->admin())->delete("/admin/categories/{$category->id}");

        $this->assertNull($product->fresh()->category_id);
    }

    public function test_product_image_is_uploaded_replaced_and_deleted_from_disk(): void
    {
        $this->actingAs($this->admin())->post('/admin/products', $this->productPayload())
            ->assertSessionHasNoErrors()->assertRedirect('/admin/products');

        $product = Product::where('name', 'Test Product')->firstOrFail();
        Storage::disk('uploads')->assertExists($product->image);
        $oldPath = $product->image;

        $this->actingAs($this->admin())->put("/admin/products/{$product->id}", [
            'name' => 'Test Product',
            'price' => 5000,
            'stock' => 10,
            'rating' => 4.5,
            'reviews_count' => 3,
            'image' => UploadedFile::fake()->image('new.png', 800, 800),
        ])->assertSessionHasNoErrors();

        $product->refresh();
        Storage::disk('uploads')->assertMissing($oldPath);
        Storage::disk('uploads')->assertExists($product->image);

        $this->actingAs($this->admin())->delete("/admin/products/{$product->id}");
        Storage::disk('uploads')->assertMissing($product->image);
    }

    public function test_product_update_without_new_image_keeps_existing_file(): void
    {
        $this->actingAs($this->admin())->post('/admin/products', $this->productPayload());
        $product = Product::where('name', 'Test Product')->firstOrFail();

        $this->actingAs($this->admin())->put("/admin/products/{$product->id}", [
            'name' => 'Renamed',
            'price' => 6000,
            'stock' => 10,
            'rating' => 4.5,
            'reviews_count' => 3,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $product->fresh()->name);
        Storage::disk('uploads')->assertExists($product->image);
    }

    public function test_product_image_validation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/products', $this->productPayload(['image' => null]))
            ->assertSessionHasErrors('image');

        $this->actingAs($admin)->post('/admin/products', $this->productPayload([
            'image' => UploadedFile::fake()->create('script.php', 10, 'application/x-php'),
        ]))->assertSessionHasErrors('image');

        $this->actingAs($admin)->post('/admin/products', $this->productPayload([
            'image' => UploadedFile::fake()->image('huge.jpg', 600, 600)->size(3000),
        ]))->assertSessionHasErrors('image');

        $this->actingAs($admin)->post('/admin/products', $this->productPayload([
            'image' => UploadedFile::fake()->image('tiny.jpg', 50, 50),
        ]))->assertSessionHasErrors('image');

        $this->actingAs($admin)->post('/admin/products', $this->productPayload([
            'image' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'),
        ]))->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('products', ['name' => 'Test Product']);
    }

    public function test_external_url_images_are_not_touched_on_delete(): void
    {
        $product = Product::first();
        $this->assertStringStartsWith('http', $product->image);

        $this->actingAs($this->admin())->delete("/admin/products/{$product->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_settings_logo_upload_replace_and_remove(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/settings', [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertSessionHasNoErrors();

        $first = Setting::get('logo');
        Storage::disk('uploads')->assertExists($first);
        $this->assertStringContainsString('uploads/', Setting::imageUrl($first));

        $this->actingAs($admin)->post('/admin/settings', [
            'logo' => UploadedFile::fake()->image('logo2.png', 220, 220),
        ])->assertSessionHasNoErrors();

        $second = Setting::get('logo');
        Storage::disk('uploads')->assertMissing($first);
        Storage::disk('uploads')->assertExists($second);

        $this->actingAs($admin)->post('/admin/settings', ['remove_image' => ['logo']])->assertSessionHasNoErrors();
        Storage::disk('uploads')->assertMissing($second);
        $this->assertSame(config('landing.groups.brand.fields.logo.default'), Setting::allWithDefaults()['logo']);
    }

    public function test_settings_image_validation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/settings', [
            'logo' => UploadedFile::fake()->create('evil.php', 5, 'application/x-php'),
        ])->assertSessionHasErrors('logo');

        $this->actingAs($admin)->post('/admin/settings', [
            'favicon' => UploadedFile::fake()->image('big.png', 100, 100)->size(1024),
        ])->assertSessionHasErrors('favicon');

        $this->actingAs($admin)->post('/admin/settings', [
            'hero_image' => UploadedFile::fake()->image('small.jpg', 100, 100),
        ])->assertSessionHasErrors('hero_image');
    }

    public function test_contact_and_social_settings_validation(): void
    {
        $this->actingAs($this->admin())->post('/admin/settings', [
            'phone' => 'abc',
            'whatsapp' => '+88-018',
            'email' => 'not-an-email',
            'facebook_url' => 'javascript:alert(1)',
            'instagram_url' => 'not a url',
            'facebook_pixel_id' => "123'); alert(1);//",
            'delivery_inside' => 'free',
        ])->assertSessionHasErrors(['phone', 'whatsapp', 'email', 'facebook_url', 'instagram_url', 'facebook_pixel_id', 'delivery_inside']);
    }

    public function test_landing_page_content_comes_from_settings_reviews_and_faqs(): void
    {
        $this->actingAs($this->admin())->post('/admin/settings', [
            'hero_highlight' => 'ডাইনামিক হাইলাইট',
            'brand_name' => 'MY BRAND',
            'whatsapp' => '8801700000000',
            'facebook_url' => 'https://www.facebook.com/mybrand',
            'trust_1_title' => 'কাস্টম ট্রাস্ট টাইটেল',
        ])->assertSessionHasNoErrors();

        Review::create(['name' => 'টেস্ট গ্রাহক', 'body' => 'অসাধারণ সার্ভিস', 'rating' => 4]);
        Faq::create(['question' => 'টেস্ট প্রশ্ন?', 'answer' => 'টেস্ট উত্তর']);
        Review::create(['name' => 'লুকানো গ্রাহক', 'body' => 'hidden review', 'rating' => 5, 'is_active' => false]);

        $this->get('/')->assertOk()
            ->assertSee('ডাইনামিক হাইলাইট')
            ->assertSee('MY BRAND')
            ->assertSee('8801700000000')
            ->assertSee('https://www.facebook.com/mybrand', false)
            ->assertSee('কাস্টম ট্রাস্ট টাইটেল')
            ->assertSee('অসাধারণ সার্ভিস')
            ->assertSee('টেস্ট প্রশ্ন?')
            ->assertDontSee('hidden review');
    }

    public function test_landing_hides_empty_sections_and_social_links(): void
    {
        Review::query()->delete();
        Faq::query()->delete();

        $this->get('/')->assertOk()
            ->assertDontSee('class="reviews-section"', false)
            ->assertDontSee('class="faq-section"', false)
            ->assertDontSee('ইনস্টাগ্রাম');
    }

    public function test_landing_uses_uploaded_logo_and_product_images(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/settings', ['logo' => UploadedFile::fake()->image('logo.png', 200, 200)]);
        $this->actingAs($admin)->post('/admin/products', $this->productPayload());

        $logo = Setting::imageUrl(Setting::get('logo'));
        $productImage = Product::where('name', 'Test Product')->firstOrFail()->image_url;

        $response = $this->get('/')->assertOk()->assertSee($logo, false);
        $this->assertStringContainsString(json_encode($productImage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), str_replace('\/', '/', $response->getContent()));
    }

    public function test_review_and_faq_crud(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/reviews')->assertOk();
        $this->actingAs($admin)->get('/admin/faqs')->assertOk();

        $this->actingAs($admin)->post('/admin/reviews', ['name' => 'Rahim', 'body' => 'Good', 'rating' => 5, 'is_active' => 1])->assertSessionHasNoErrors();
        $review = Review::where('name', 'Rahim')->firstOrFail();
        $this->actingAs($admin)->get("/admin/reviews/{$review->id}/edit")->assertOk();
        $this->actingAs($admin)->put("/admin/reviews/{$review->id}", ['name' => 'Rahim', 'body' => 'Great', 'rating' => 4])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'body' => 'Great', 'is_active' => 0]);
        $this->actingAs($admin)->post('/admin/reviews', ['name' => '', 'body' => '', 'rating' => 9])->assertSessionHasErrors(['name', 'body', 'rating']);
        $this->actingAs($admin)->delete("/admin/reviews/{$review->id}");
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);

        $this->actingAs($admin)->post('/admin/faqs', ['question' => 'Q?', 'answer' => 'A', 'is_active' => 1])->assertSessionHasNoErrors();
        $faq = Faq::where('question', 'Q?')->firstOrFail();
        $this->actingAs($admin)->get("/admin/faqs/{$faq->id}/edit")->assertOk();
        $this->actingAs($admin)->put("/admin/faqs/{$faq->id}", ['question' => 'Q2?', 'answer' => 'A2'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/faqs', ['question' => '', 'answer' => ''])->assertSessionHasErrors(['question', 'answer']);
        $this->actingAs($admin)->delete("/admin/faqs/{$faq->id}");
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_content_management_requires_login(): void
    {
        foreach (['/admin/categories', '/admin/reviews', '/admin/faqs', '/admin/settings'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_reseeding_does_not_reset_admin_password_or_edited_settings(): void
    {
        $admin = $this->admin();
        $admin->update(['password' => 'Another-Str0ng-Pass!']);
        Setting::set('phone', '01999999999');

        $this->seed();

        $this->assertTrue(Hash::check('Another-Str0ng-Pass!', $admin->fresh()->password));
        $this->assertSame('01999999999', Setting::get('phone'));
    }

    public function test_order_deducts_stock_and_cancel_or_delete_restores_it(): void
    {
        $product = Product::first();
        $product->update(['stock' => 5]);
        $admin = $this->admin();

        $this->postJson('/order', [
            'customer_name' => 'Test',
            'customer_phone' => '01805962220',
            'customer_address' => 'Dhaka',
            'delivery_area' => 'inside',
            'items' => [['id' => $product->id, 'qty' => 2]],
        ])->assertOk();

        $this->assertSame(3, $product->fresh()->stock);
        $order = Order::firstOrFail();

        $this->actingAs($admin)->patch("/admin/orders/{$order->id}/status", ['status' => 'cancelled'])
            ->assertSessionHas('success', "অর্ডারের স্ট্যাটাস সফলভাবে 'বাতিল' হিসেবে আপডেট করা হয়েছে।");
        $this->assertSame(5, $product->fresh()->stock);

        $this->actingAs($admin)->patch("/admin/orders/{$order->id}/status", ['status' => 'pending']);
        $this->assertSame(3, $product->fresh()->stock);

        $this->actingAs($admin)->delete("/admin/orders/{$order->id}");
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_order_is_rejected_when_stock_is_insufficient(): void
    {
        $product = Product::first();
        $product->update(['stock' => 1]);

        $this->postJson('/order', [
            'customer_name' => 'Test',
            'customer_phone' => '01805962220',
            'customer_address' => 'Dhaka',
            'delivery_area' => 'inside',
            'items' => [['id' => $product->id, 'qty' => 2]],
        ])->assertStatus(422)->assertJson(['success' => false]);

        $this->assertSame(1, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_product_form_requires_valid_stock_and_rating(): void
    {
        $this->actingAs($this->admin())->post('/admin/products', $this->productPayload(['stock' => -1, 'rating' => 9, 'reviews_count' => 'x']))
            ->assertSessionHasErrors(['stock', 'rating', 'reviews_count']);
    }

    public function test_landing_marks_out_of_stock_products(): void
    {
        $product = Product::first();
        $product->update(['stock' => 0]);

        $this->get('/')->assertOk()->assertSee('"stock":0', false);
    }

    public function test_hero_card_can_follow_a_chosen_product(): void
    {
        $product = Product::first();
        $product->update(['name' => 'হিরো টেস্ট প্রোডাক্ট', 'price' => 12345, 'old_price' => 15000]);

        $this->actingAs($this->admin())->post('/admin/settings', ['hero_product_id' => $product->id])->assertSessionHasNoErrors();

        $this->get('/')->assertOk()->assertSee('হিরো টেস্ট প্রোডাক্ট')->assertSee('৳১২,৩৪৫')->assertSee('৳১৫,০০০');

        $this->actingAs($this->admin())->post('/admin/settings', ['hero_product_id' => 99999])->assertSessionHasErrors('hero_product_id');
        $this->actingAs($this->admin())->get('/admin/settings')->assertOk()->assertSee('হিরো কার্ডে দেখানোর প্রোডাক্ট');
    }

    public function test_order_accepts_bangla_digit_and_prefixed_phone_numbers(): void
    {
        $product = Product::first();

        foreach (['০১৮০৫৯৬২২২০', '+880 1805-962220', '8801805962220'] as $phone) {
            $this->postJson('/order', [
                'customer_name' => 'Test',
                'customer_phone' => $phone,
                'customer_address' => 'Dhaka',
                'delivery_area' => 'inside',
                'items' => [['id' => $product->id, 'qty' => 1]],
            ])->assertOk();
        }

        $this->assertSame(3, Order::where('customer_phone', '01805962220')->count());
    }

    public function test_products_with_same_or_non_latin_names_get_unique_slugs(): void
    {
        $admin = $this->admin();

        foreach (range(1, 2) as $ignored) {
            $this->actingAs($admin)->post('/admin/products', $this->productPayload(['name' => 'দোলনা চেয়ার']))->assertSessionHasNoErrors();
        }

        $slugs = Product::where('name', 'দোলনা চেয়ার')->pluck('slug');
        $this->assertCount(2, $slugs);
        $this->assertCount(2, $slugs->unique());
    }

    public function test_old_price_cannot_be_lower_than_price(): void
    {
        $this->actingAs($this->admin())->post('/admin/products', $this->productPayload(['price' => 5000, 'old_price' => 4000]))
            ->assertSessionHasErrors('old_price');
    }

    public function test_settings_page_is_split_into_tabs_and_opens_the_tab_with_an_error(): void
    {
        $admin = $this->admin();

        $page = $this->actingAs($admin)->get('/admin/settings')->assertOk();
        foreach (config('landing.tabs') as $tabId => $tab) {
            $page->assertSee('data-tab="'.$tabId.'"', false)->assertSee($tab['label']);
        }
        $page->assertSee('id="tab-general" role="tabpanel"', false);
        $this->assertStringContainsString('settings-panel active" id="tab-general"', $page->getContent());

        $errorPage = $this->actingAs($admin)->followingRedirects()->from('/admin/settings')
            ->post('/admin/settings', ['facebook_pixel_id' => 'bad-pixel']);

        $this->assertStringContainsString('settings-panel active" id="tab-seo"', $errorPage->getContent());
        $this->assertStringNotContainsString('settings-panel active" id="tab-general"', $errorPage->getContent());
    }
}
