<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    /** Every request in this file targets this host, whatever APP_URL says in the developer's .env. */
    private const SITE = 'http://localhost';

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl(self::SITE);
    }

    private function visit(string $path): TestResponse
    {
        return $this->get(self::SITE.$path);
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonLd(string $html): array
    {
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);

        return json_decode($match[1] ?? '{}', true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $jsonLd
     * @return array<string, mixed>|null
     */
    private function node(array $jsonLd, string $type): ?array
    {
        foreach ($jsonLd['@graph'] ?? [] as $node) {
            if ($node['@type'] === $type) {
                return $node;
            }
        }

        return null;
    }

    // ---------- landing page ----------

    public function test_landing_page_carries_the_standard_search_and_sharing_tags(): void
    {
        $this->visit('/')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="http://localhost/">', false)
            ->assertSee('<meta name="robots" content="index, follow', false)
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertSee('<meta property="og:locale" content="bn_BD">', false)
            ->assertSee('<meta property="og:site_name"', false)
            ->assertSee('<meta property="og:url" content="http://localhost/">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertDontSee('name="keywords"', false);
    }

    public function test_landing_page_structured_data_describes_the_business_and_lists_the_products(): void
    {
        Setting::set('facebook_url', 'https://www.facebook.com/armetalshop');
        $first = Product::factory()->create(['name' => 'সুইং চেয়ার']);
        Product::factory()->create(['name' => 'বন্ধ প্রোডাক্ট', 'is_active' => false]);

        $ld = $this->jsonLd($this->visit('/')->assertOk()->getContent());

        $this->assertSame('https://schema.org', $ld['@context']);

        $store = $this->node($ld, 'FurnitureStore');
        $this->assertSame('http://localhost/#organization', $store['@id']);
        $this->assertSame('BD', $store['address']['addressCountry']);
        $this->assertSame('+8801805962220', $store['telephone']);
        $this->assertContains('https://www.facebook.com/armetalshop', $store['sameAs']);

        $this->assertSame('bn-BD', $this->node($ld, 'WebSite')['inLanguage']);

        $list = $this->node($ld, 'ItemList')['itemListElement'];
        $this->assertCount(1, $list);
        $this->assertSame('http://localhost/products/'.$first->slug, $list[0]['url']);
    }

    public function test_landing_page_links_to_every_product_page_in_plain_html(): void
    {
        $product = Product::factory()->create();
        $hidden = Product::factory()->create(['is_active' => false]);

        $this->visit('/')
            ->assertOk()
            ->assertSee('class="footer-product-list"', false)
            ->assertSee(route('products.show', $product->slug), false)
            ->assertDontSee(route('products.show', $hidden->slug), false);
    }

    public function test_the_main_address_setting_replaces_the_request_address_everywhere(): void
    {
        Setting::set('site_url', 'https://www.example.com');
        $product = Product::factory()->create();

        $landing = $this->visit('/')->assertOk();
        $landing->assertSee('<link rel="canonical" href="https://www.example.com/">', false);
        $this->assertSame('https://www.example.com/#organization', $this->node($this->jsonLd($landing->getContent()), 'FurnitureStore')['@id']);

        $this->get(route('products.show', $product->slug))
            ->assertSee('<link rel="canonical" href="https://www.example.com/products/'.$product->slug.'">', false);

        $this->visit('/sitemap.xml')->assertSee('<loc>https://www.example.com/products/'.$product->slug.'</loc>', false);
        $this->visit('/robots.txt')->assertSee('Sitemap: https://www.example.com/sitemap.xml', false);
    }

    public function test_the_main_address_setting_must_be_a_real_web_address(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/settings', ['site_url' => 'not a url'])
            ->assertSessionHasErrors('site_url');
    }

    // ---------- product page ----------

    public function test_product_page_shows_the_product_with_its_own_title_and_canonical_address(): void
    {
        $category = Category::create(['name' => 'সুইং চেয়ার', 'slug' => 'swing-chair', 'sort_order' => 1, 'is_active' => true]);
        $product = Product::factory()->create([
            'name' => 'প্রিমিয়াম দোলনা',
            'price' => 15500,
            'old_price' => 18000,
            'short_description' => 'মজবুত ও আকর্ষণীয় দোলনা।',
            'category_id' => $category->id,
        ]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('<h1 class="pp-title">প্রিমিয়াম দোলনা</h1>', false)
            ->assertSee('<title>প্রিমিয়াম দোলনা | ৳15,500 | AR METAL</title>', false)
            ->assertSee('<meta name="description" content="মজবুত ও আকর্ষণীয় দোলনা।">', false)
            ->assertSee('<link rel="canonical" href="http://localhost/products/'.$product->slug.'">', false)
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="product:price:amount" content="15500.00">', false)
            ->assertSee('<meta property="product:price:currency" content="BDT">', false)
            ->assertSee('১৩% ছাড়')
            ->assertSee('স্টকে আছে')
            ->assertSee('সুইং চেয়ার');
    }

    public function test_product_structured_data_has_price_availability_and_breadcrumbs_but_no_invented_ratings(): void
    {
        $product = Product::factory()->create(['price' => 15500, 'stock' => 5, 'rating' => 5, 'reviews_count' => 48]);

        $html = $this->get(route('products.show', $product->slug))->assertOk()->getContent();
        $ld = $this->jsonLd($html);

        $node = $this->node($ld, 'Product');
        $this->assertSame($product->name, $node['name']);
        $this->assertSame('ARM-'.$product->id, $node['sku']);
        $this->assertSame('BDT', $node['offers']['priceCurrency']);
        $this->assertSame('15500.00', $node['offers']['price']);
        $this->assertSame('https://schema.org/InStock', $node['offers']['availability']);
        $this->assertSame('http://localhost/products/'.$product->slug, $node['offers']['url']);

        $crumbs = $this->node($ld, 'BreadcrumbList')['itemListElement'];
        $this->assertSame(['হোম', $product->name], array_column($crumbs, 'name'));

        $this->assertStringNotContainsString('aggregateRating', $html);
        $this->assertStringNotContainsString('"review"', $html);
    }

    public function test_a_sold_out_product_is_marked_out_of_stock(): void
    {
        $product = Product::factory()->create(['stock' => 0]);

        $response = $this->get(route('products.show', $product->slug))->assertOk()->assertSee('স্টক শেষ');

        $this->assertSame('https://schema.org/OutOfStock', $this->node($this->jsonLd($response->getContent()), 'Product')['offers']['availability']);
        $this->assertStringNotContainsString(route('home', ['buy' => $product->id]), $response->getContent());
    }

    public function test_hidden_and_unknown_products_return_404(): void
    {
        $hidden = Product::factory()->create(['is_active' => false]);

        $this->get(route('products.show', $hidden->slug))->assertNotFound();
        $this->visit('/products/no-such-product')->assertNotFound();
    }

    public function test_the_order_button_sends_the_visitor_to_the_checkout_with_this_product_selected(): void
    {
        $product = Product::factory()->create(['stock' => 3]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee(route('home', ['buy' => $product->id]).'#checkoutSection', false);
    }

    public function test_description_specs_and_related_products_are_part_of_the_page(): void
    {
        $product = Product::factory()->create([
            'description' => "প্রথম অনুচ্ছেদ\nদ্বিতীয় অনুচ্ছেদ",
            'specs' => ['- ব্র্যান্ড: AR Metal', 'কাস্টমাইজেশন সম্ভব'],
        ]);
        $related = Product::factory()->create(['name' => 'অন্য প্রোডাক্ট']);
        $hidden = Product::factory()->create(['name' => 'লুকানো প্রোডাক্ট', 'is_active' => false]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee("প্রথম অনুচ্ছেদ\nদ্বিতীয় অনুচ্ছেদ")
            ->assertSee('<span class="pp-spec-key">ব্র্যান্ড</span><span class="pp-spec-val">AR Metal</span>', false)
            ->assertSee('কাস্টমাইজেশন সম্ভব')
            ->assertSee('অন্য প্রোডাক্ট')
            ->assertDontSee('লুকানো প্রোডাক্ট');
    }

    public function test_the_long_text_typed_into_the_short_description_still_becomes_the_page_description(): void
    {
        $product = Product::factory()->create(['description' => null, 'short_description' => 'এটাই আসল বিবরণ']);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('<div class="pp-text">এটাই আসল বিবরণ</div>', false);
    }

    // ---------- sitemap and robots ----------

    public function test_sitemap_lists_the_home_page_and_every_active_product_with_its_photo(): void
    {
        $product = Product::factory()->create(['image' => 'https://example.com/photo.jpg']);
        $hidden = Product::factory()->create(['is_active' => false]);

        $response = $this->visit('/sitemap.xml')->assertOk();
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'the sitemap must be well-formed XML');

        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->registerXPathNamespace('i', 'http://www.google.com/schemas/sitemap-image/1.1');

        $locations = array_map('strval', $xml->xpath('//s:url/s:loc'));
        $this->assertSame(['http://localhost/', 'http://localhost/products/'.$product->slug], $locations);
        $this->assertNotContains('http://localhost/products/'.$hidden->slug, $locations);
        $this->assertSame('https://example.com/photo.jpg', (string) $xml->xpath('//i:image/i:loc')[0]);
        $this->assertNotEmpty((string) $xml->xpath('//s:url/s:lastmod')[1]);
    }

    public function test_sitemap_still_works_when_old_products_have_missing_or_placeholder_dates(): void
    {
        // Products brought in from an old database dump can have no dates, or a placeholder date from long ago.
        // (MySQL refuses outright invalid dates, so those cannot exist on the live server.)
        $noDates = Product::factory()->create();
        $brokenDates = Product::factory()->create();
        $normal = Product::factory()->create();
        DB::table('products')->where('id', $noDates->id)->update(['created_at' => null, 'updated_at' => null]);
        DB::table('products')->where('id', $brokenDates->id)->update(['created_at' => '1971-06-15 12:00:00', 'updated_at' => '1971-06-15 12:00:00']);

        $response = $this->visit('/sitemap.xml')->assertOk();

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'the sitemap must be well-formed XML');
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertCount(4, $xml->xpath('//s:url'));
        foreach ($xml->xpath('//s:url/s:lastmod') as $lastModified) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', (string) $lastModified);
        }
        $this->assertStringContainsString('/products/'.$normal->slug, $response->getContent());
    }

    public function test_sitemap_starts_with_the_xml_declaration(): void
    {
        $this->visit('/sitemap.xml')->assertOk()->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false);

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $this->visit('/sitemap.xml')->getContent());
    }

    public function test_no_view_contains_a_literal_xml_style_tag(): void
    {
        // With PHP's short_open_tag enabled, Blade reads "<?xml" as PHP code and the page fails with a server error.
        // That setting is common on live servers but off in development, so it is guarded here.
        $offenders = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file): bool => str_ends_with($file->getFilename(), '.php')
                && preg_match('/<\?(?!php|=)/', $file->getContents()))
            ->map(fn ($file): string => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame([], $offenders, 'Put XML declarations in a controller, not in a view.');
    }

    public function test_robots_txt_hides_admin_pages_and_points_to_the_sitemap(): void
    {
        $response = $this->visit('/robots.txt')->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $response->assertSee('User-agent: *', false)
            ->assertSee('Disallow: /admin', false)
            ->assertSee('Disallow: /login', false)
            ->assertSee('Disallow: /*?buy=', false)
            ->assertSee('Sitemap: http://localhost/sitemap.xml', false)
            ->assertDontSee('Disallow: /uploads', false);
    }

    public function test_login_and_admin_pages_ask_search_engines_not_to_index_them(): void
    {
        $this->visit('/login')->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    // ---------- helpers ----------

    public function test_structured_data_cannot_break_out_of_its_script_tag(): void
    {
        $product = Product::factory()->create(['name' => 'ঝুঁকি </script><script>alert(1)</script> "কোটেশন"']);

        $html = $this->get(route('products.show', $product->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertSame($product->name, $this->node($this->jsonLd($html), 'Product')['name']);
    }

    public function test_local_phone_numbers_become_international_and_texts_are_cleaned_for_meta_tags(): void
    {
        $this->assertSame('+8801805962220', Seo::phone('01805962220'));
        $this->assertSame('+8801805962220', Seo::phone('+880 1805-962220'));
        $this->assertNull(Seo::phone(''));

        $this->assertSame('একটি দুটি তিনটি', Seo::describe("<p>একটি</p>\n  দুটি   তিনটি"));
        $this->assertLessThanOrEqual(60, mb_strlen(Seo::describe(str_repeat('শব্দ ', 100), 50)));
    }
}
