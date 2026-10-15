<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProductSeoFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploads');
        URL::forceRootUrl('http://localhost');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'দোলনা সুইং চেয়ার',
            'price' => 5000,
            'stock' => 10,
            'rating' => 4.5,
            'reviews_count' => 3,
        ], $overrides);
    }

    public function test_product_page_uses_the_search_title_and_description_the_admin_wrote(): void
    {
        $product = Product::factory()->create([
            'name' => 'দোলনা',
            'seo_title' => 'দোলনা সুইং চেয়ার দাম | AR Metal BD',
            'seo_description' => 'নিজস্ব কারখানায় তৈরি মজবুত দোলনা।',
            'short_description' => 'এটা সংক্ষিপ্ত বিবরণ।',
        ]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('<title>দোলনা সুইং চেয়ার দাম | AR Metal BD</title>', false)
            ->assertSee('<meta name="description" content="নিজস্ব কারখানায় তৈরি মজবুত দোলনা।">', false)
            ->assertSee('<meta property="og:title" content="দোলনা সুইং চেয়ার দাম | AR Metal BD">', false);
    }

    public function test_product_page_falls_back_to_the_automatic_texts_when_the_fields_are_empty_or_blank(): void
    {
        $product = Product::factory()->create([
            'name' => 'দোলনা',
            'price' => 15500,
            'seo_title' => '   ',
            'seo_description' => null,
            'short_description' => 'মজবুত দোলনা।',
        ]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('<title>দোলনা | ৳15,500 | AR METAL</title>', false)
            ->assertSee('<meta name="description" content="মজবুত দোলনা।">', false);
    }

    public function test_admin_can_save_the_search_texts_when_creating_and_editing_a_product(): void
    {
        $admin = User::factory()->create();
        $image = UploadedFile::fake()->image('photo.jpg', 900, 900);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->payload([
                'image' => $image,
                'seo_title' => 'প্রথম টাইটেল',
                'seo_description' => 'প্রথম বিবরণ',
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product = Product::firstOrFail();
        $this->assertSame('প্রথম টাইটেল', $product->seo_title);
        $this->assertSame('প্রথম বিবরণ', $product->seo_description);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $this->payload([
                'seo_title' => 'দ্বিতীয় টাইটেল',
                'seo_description' => '',
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('দ্বিতীয় টাইটেল', $product->seo_title);
        $this->assertNull($product->seo_description);
    }

    public function test_the_search_texts_have_length_limits(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $this->payload([
                'seo_title' => str_repeat('ক', 121),
                'seo_description' => str_repeat('ক', 321),
            ]))
            ->assertSessionHasErrors(['seo_title', 'seo_description']);
    }

    public function test_edit_and_create_forms_show_the_search_fields_with_saved_values(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['seo_title' => 'সেভ করা টাইটেল', 'seo_description' => 'সেভ করা বিবরণ']);

        $this->actingAs($admin)->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('name="seo_title"', false)
            ->assertSee('সেভ করা টাইটেল')
            ->assertSee('সেভ করা বিবরণ');

        $this->actingAs($admin)->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('name="seo_title"', false)
            ->assertSee('name="seo_description"', false);
    }
}
