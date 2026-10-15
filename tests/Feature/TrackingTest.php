<?php

namespace Tests\Feature;

use App\Models\IncompleteOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Support\MetaTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '3f1c2c9e-7a5b-4e0e-9a43-0d6f2b1c8a11';

    private const ACCESS_TOKEN = 'EAAGsecretaccesstokenforthetestsonly1234567890';

    private const FBP = 'fb.1.1700000000000.1234567890';

    private const FBC = 'fb.1.1700000000000.AbCdEfGhIjKl';

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeMeta();
    }

    /**
     * Replace the pretend Meta server. Calling Http::fake twice would keep the first answer.
     */
    private function fakeMeta(mixed $answer = null): void
    {
        Http::swap(new HttpFactory);
        Http::fake(['graph.facebook.com/*' => $answer ?? Http::response(['events_received' => 1, 'fbtrace_id' => 'TRACE123'], 200)]);
    }

    private function enableServerTracking(): void
    {
        Setting::set('facebook_pixel_id', '1234567890');
        Setting::setSecret('meta_capi_token', self::ACCESS_TOKEN);
        Setting::set('meta_capi_enabled', '1');
    }

    private function hash(string $value): string
    {
        return hash('sha256', mb_strtolower(trim($value)));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function orderPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'রহিম উদ্দিন সাহেব',
            'customer_phone' => '01712345678',
            'customer_address' => 'মিরপুর-১০, ঢাকা',
            'delivery_area' => 'inside',
            'visitor_token' => self::TOKEN,
            'items' => [['id' => $product->id, 'qty' => 2]],
            'fbp' => self::FBP,
            'fbc' => self::FBC,
            'utm_source' => 'facebook',
            'utm_medium' => 'paid',
            'utm_campaign' => 'eid-offer',
            'referrer' => 'l.facebook.com',
        ], $overrides);
    }

    /**
     * The first request to Meta carrying this event, or null.
     *
     * @return array<string, mixed>|null
     */
    private function sentEvent(string $name): ?array
    {
        foreach (Http::recorded() as [$request]) {
            $event = $request->data()['data'][0] ?? null;

            if ($event && $event['event_name'] === $name) {
                return $event;
            }
        }

        return null;
    }

    private function sentCount(): int
    {
        return Http::recorded()->count();
    }

    // ---------- the secret token ----------

    public function test_the_access_token_is_stored_encrypted_and_never_reaches_the_landing_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.tracking.update'), ['meta_capi_token' => self::ACCESS_TOKEN, 'meta_capi_enabled' => '1'])
            ->assertSessionHasNoErrors();

        $stored = Setting::where('key', 'meta_capi_token')->value('value');

        $this->assertNotSame(self::ACCESS_TOKEN, $stored);
        $this->assertStringNotContainsString('secretaccesstoken', $stored);
        $this->assertSame(self::ACCESS_TOKEN, Setting::secret('meta_capi_token'));
        $this->assertArrayNotHasKey('meta_capi_token', Setting::allWithDefaults());

        $this->get('/')->assertOk()->assertDontSee('secretaccesstoken');
    }

    public function test_the_admin_page_only_shows_the_last_four_characters_of_the_token(): void
    {
        $this->enableServerTracking();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.tracking.index'))
            ->assertOk()
            ->assertSee('শেষ ৪ অক্ষর: 7890')
            ->assertDontSee(self::ACCESS_TOKEN);
    }

    public function test_leaving_the_token_blank_keeps_it_and_the_remove_box_clears_it(): void
    {
        $this->enableServerTracking();
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.tracking.update'), ['meta_capi_token' => '', 'meta_test_event_code' => 'TEST123'])
            ->assertSessionHasNoErrors();
        $this->assertSame(self::ACCESS_TOKEN, Setting::secret('meta_capi_token'));
        $this->assertSame('TEST123', Setting::get('meta_test_event_code'));

        $this->actingAs($admin)->put(route('admin.tracking.update'), ['remove_token' => '1'])->assertSessionHasNoErrors();
        $this->assertNull(Setting::secret('meta_capi_token'));
        $this->assertSame('0', Setting::get('meta_capi_enabled'));
    }

    public function test_bad_token_and_test_code_formats_are_rejected(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.tracking.update'), ['meta_capi_token' => 'short'])->assertSessionHasErrors('meta_capi_token');
        $this->actingAs($admin)->put(route('admin.tracking.update'), ['meta_capi_token' => 'has a space inside it which is long enough'])->assertSessionHasErrors('meta_capi_token');
        $this->actingAs($admin)->put(route('admin.tracking.update'), ['meta_test_event_code' => 'bad code!'])->assertSessionHasErrors('meta_test_event_code');
    }

    public function test_only_logged_in_admins_can_open_or_change_tracking(): void
    {
        $this->get(route('admin.tracking.index'))->assertRedirect('/login');
        $this->put(route('admin.tracking.update'), ['meta_capi_token' => self::ACCESS_TOKEN])->assertRedirect('/login');
        $this->post(route('admin.tracking.test'))->assertRedirect('/login');

        $this->assertNull(Setting::secret('meta_capi_token'));
    }

    // ---------- Purchase from the storefront ----------

    public function test_an_order_sends_a_purchase_to_meta_with_hashed_customer_details(): void
    {
        $this->enableServerTracking();
        $product = Product::factory()->create(['price' => 15500, 'stock' => 5]);

        $response = $this->postJson(route('landing.order'), $this->orderPayload($product))->assertOk();

        $order = Order::firstOrFail();
        $response->assertJsonPath('event_id', 'purchase-'.$order->id)
            ->assertJsonPath('pixel_purchase_payload.content_type', 'product')
            ->assertJsonPath('pixel_purchase_payload.order_id', $order->order_number);

        Http::assertSent(function (Request $request) use ($order): bool {
            $body = $request->data();

            return $request->url() === 'https://graph.facebook.com/v23.0/1234567890/events'
                && $body['access_token'] === self::ACCESS_TOKEN
                && ! array_key_exists('test_event_code', $body)
                && $body['data'][0]['event_name'] === 'Purchase'
                && $body['data'][0]['event_id'] === 'purchase-'.$order->id
                && $body['data'][0]['action_source'] === 'website';
        });

        $event = $this->sentEvent('Purchase');
        $user = $event['user_data'];

        $this->assertSame([$this->hash('8801712345678')], $user['ph']);
        $this->assertSame([$this->hash('রহিম')], $user['fn']);
        $this->assertSame([$this->hash('উদ্দিন সাহেব')], $user['ln']);
        $this->assertSame([$this->hash('dhaka')], $user['ct']);
        $this->assertSame([$this->hash('bd')], $user['country']);
        $this->assertSame([self::TOKEN], $user['external_id']);
        $this->assertSame(self::FBP, $user['fbp']);
        $this->assertSame(self::FBC, $user['fbc']);
        $this->assertNotEmpty($user['client_ip_address']);
        $this->assertStringNotContainsString('01712345678', json_encode($event));

        $custom = $event['custom_data'];
        $this->assertSame('BDT', $custom['currency']);
        $this->assertEquals($order->grand_total, $custom['value']);
        $this->assertSame('product', $custom['content_type']);
        $this->assertSame([(string) $product->id], $custom['content_ids']);
        $this->assertSame([['id' => (string) $product->id, 'quantity' => 2, 'item_price' => 15500.0]], $custom['contents']);
        $this->assertSame(2, $custom['num_items']);
        $this->assertSame($order->order_number, $custom['order_id']);

        $this->assertDatabaseHas('tracking_events', [
            'event_name' => 'Purchase', 'event_id' => 'purchase-'.$order->id, 'status' => 'sent',
            'events_received' => 1, 'fbtrace_id' => 'TRACE123', 'reference' => $order->order_number,
        ]);
    }

    public function test_the_campaign_the_customer_came_from_is_saved_with_the_order(): void
    {
        $product = Product::factory()->create();

        $this->postJson(route('landing.order'), $this->orderPayload($product))->assertOk();

        $this->assertDatabaseHas('orders', [
            'visitor_token' => self::TOKEN, 'fbp' => self::FBP, 'fbc' => self::FBC,
            'utm_source' => 'facebook', 'utm_medium' => 'paid', 'utm_campaign' => 'eid-offer', 'referrer' => 'l.facebook.com',
        ]);
    }

    public function test_a_failing_meta_never_stops_or_slows_an_order_and_the_failure_is_recorded(): void
    {
        $this->enableServerTracking();
        $this->fakeMeta(Http::response(['error' => ['message' => 'Invalid OAuth access token', 'code' => 190, 'fbtrace_id' => 'BAD1']], 400));
        $product = Product::factory()->create();

        $this->postJson(route('landing.order'), $this->orderPayload($product))->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseCount('orders', 1);
        $event = TrackingEvent::firstOrFail();
        $this->assertSame('failed', $event->status);
        $this->assertStringContainsString('Invalid OAuth access token', $event->error);
        $this->assertStringNotContainsString('secretaccesstoken', (string) $event->error);
    }

    public function test_a_network_error_is_recorded_without_breaking_the_order(): void
    {
        $this->enableServerTracking();
        $this->fakeMeta(fn () => throw new ConnectionException('Connection timed out'));
        $product = Product::factory()->create();

        $this->postJson(route('landing.order'), $this->orderPayload($product))->assertOk();

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame('failed', TrackingEvent::firstOrFail()->status);
    }

    public function test_nothing_is_sent_without_a_token_or_when_the_admin_switched_it_off(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->postJson(route('landing.order'), $this->orderPayload($product))->assertOk();

        $this->enableServerTracking();
        Setting::set('meta_capi_enabled', '0');
        $this->postJson(route('landing.order'), $this->orderPayload($product, ['customer_phone' => '01812345678']))->assertOk();

        Http::assertNothingSent();
        $this->assertDatabaseCount('tracking_events', 0);
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_malformed_tracking_values_from_the_browser_are_dropped_instead_of_refusing_the_order(): void
    {
        $product = Product::factory()->create();

        $this->postJson(route('landing.order'), $this->orderPayload($product, [
            'fbp' => '<script>alert(1)</script>',
            'fbc' => str_repeat('x', 500),
            'utm_source' => '<b>facebook</b>'.str_repeat('a', 400),
        ]))->assertOk();

        $order = Order::firstOrFail();
        $this->assertNull($order->fbp);
        $this->assertNull($order->fbc);
        $this->assertStringStartsWith('facebook', $order->utm_source);
        $this->assertLessThanOrEqual(255, mb_strlen($order->utm_source));
    }

    public function test_browser_values_are_cleaned_by_the_tracker(): void
    {
        $request = \Illuminate\Http\Request::create('/', 'POST', ['fbp' => self::FBP, 'utm_campaign' => '  Eid  ', 'referrer' => '']);

        $clean = MetaTracker::clientData($request);

        $this->assertSame(self::FBP, $clean['fbp']);
        $this->assertSame('Eid', $clean['utm_campaign']);
        $this->assertNull($clean['referrer']);
        $this->assertNull($clean['utm_term']);
    }

    // ---------- Lead ----------

    public function test_a_lead_is_sent_once_when_a_phone_number_is_first_saved(): void
    {
        $this->enableServerTracking();
        $product = Product::factory()->create(['price' => 1000]);
        $payload = [
            'visitor_token' => self::TOKEN, 'customer_phone' => '01712345678', 'customer_name' => 'রহিম',
            'delivery_area' => 'inside', 'items' => [['id' => $product->id, 'qty' => 2]],
            'fbp' => self::FBP, 'utm_campaign' => 'eid-offer',
        ];

        $this->postJson(route('landing.incomplete'), $payload)->assertNoContent();
        $this->postJson(route('landing.incomplete'), array_merge($payload, ['customer_name' => 'রহিম উদ্দিন']))->assertNoContent();

        $this->assertSame(1, $this->sentCount());
        $event = $this->sentEvent('Lead');
        $this->assertSame('lead-'.self::TOKEN, $event['event_id']);
        $this->assertSame([$this->hash('8801712345678')], $event['user_data']['ph']);
        $this->assertEquals(2000, $event['custom_data']['value']);

        $this->assertDatabaseHas('incomplete_orders', ['visitor_token' => self::TOKEN, 'fbp' => self::FBP, 'utm_campaign' => 'eid-offer']);
    }

    // ---------- phone orders and confirmation ----------

    public function test_converting_an_incomplete_order_reports_the_phone_sale_and_the_confirmation(): void
    {
        $this->enableServerTracking();
        $product = Product::factory()->create(['price' => 15500, 'stock' => 5]);
        $entry = IncompleteOrder::factory()->create([
            'visitor_token' => self::TOKEN, 'customer_phone' => '01712345678', 'fbp' => self::FBP, 'utm_campaign' => 'eid-offer',
            'items' => [['product_id' => $product->id, 'name' => $product->name, 'price' => 15500.0, 'qty' => 1]],
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.incomplete-orders.convert.store', $entry), [
                'customer_name' => 'রহিম উদ্দিন', 'customer_phone' => '01712345678', 'customer_address' => 'মিরপুর',
                'delivery_area' => 'inside', 'items' => [['id' => $product->id, 'qty' => 1]],
            ])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame('eid-offer', $order->utm_campaign);
        $this->assertSame(self::TOKEN, $order->visitor_token);

        $purchase = $this->sentEvent('Purchase');
        $this->assertSame('purchase-'.$order->id, $purchase['event_id']);
        $this->assertSame('phone_call', $purchase['action_source']);
        $this->assertArrayNotHasKey('event_source_url', $purchase);
        $this->assertSame(self::FBP, $purchase['user_data']['fbp']);

        $confirmed = $this->sentEvent('OrderConfirmed');
        $this->assertSame('confirmed-'.$order->id, $confirmed['event_id']);
        $this->assertSame('system_generated', $confirmed['action_source']);
        $this->assertSame(2, $this->sentCount());
    }

    public function test_confirming_an_order_sends_order_confirmed_once(): void
    {
        $this->enableServerTracking();
        $product = Product::factory()->create(['stock' => 10]);
        $this->postJson(route('landing.order'), $this->orderPayload($product))->assertOk();
        $order = Order::firstOrFail();
        $admin = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'processing']);
        $this->assertNull($this->sentEvent('OrderConfirmed'));

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'confirmed']);
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'shipped']);
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'confirmed']);

        $this->assertSame(1, TrackingEvent::where('event_name', 'OrderConfirmed')->count());
        $confirmed = $this->sentEvent('OrderConfirmed');
        $this->assertSame('confirmed-'.$order->id, $confirmed['event_id']);
        $this->assertSame([$this->hash('8801712345678')], $confirmed['user_data']['ph']);
        $this->assertSame(self::FBP, $confirmed['user_data']['fbp']);
    }

    // ---------- the test button ----------

    public function test_the_test_button_sends_a_flagged_event_and_real_events_never_carry_the_test_code(): void
    {
        $this->enableServerTracking();
        Setting::set('meta_test_event_code', 'TEST12345');
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.tracking.test'))->assertSessionHas('success');

        Http::assertSent(fn (Request $request): bool => ($request->data()['test_event_code'] ?? null) === 'TEST12345'
            && str_starts_with($request->data()['data'][0]['event_id'], 'test-'));
        $this->assertTrue(TrackingEvent::firstOrFail()->is_test);

        $product = Product::factory()->create();
        $this->postJson(route('landing.order'), $this->orderPayload($product))->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->data()['data'][0]['event_name'] === 'Purchase'
            && ! array_key_exists('test_event_code', $request->data()));
    }

    public function test_the_test_button_explains_what_is_missing_and_shows_a_meta_error(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.tracking.test'))->assertSessionHasErrors('test');
        Http::assertNothingSent();

        $this->enableServerTracking();
        Setting::set('meta_test_event_code', 'TEST12345');
        $this->fakeMeta(Http::response(['error' => ['message' => 'Invalid parameter', 'code' => 100]], 400));

        $errors = $this->actingAs($admin)->post(route('admin.tracking.test'))
            ->assertSessionHasErrors('test')
            ->baseResponse->getSession()->get('errors');
        $this->assertStringContainsString('Invalid parameter', $errors->first('test'));
    }

    // ---------- admin report ----------

    public function test_the_admin_page_summarises_events_campaigns_and_history(): void
    {
        $this->enableServerTracking();
        TrackingEvent::factory()->create(['event_name' => 'Purchase', 'event_id' => 'purchase-1', 'value' => 15500, 'reference' => 'ARM-AAA-1111']);
        TrackingEvent::factory()->failed()->create(['event_name' => 'Purchase', 'event_id' => 'purchase-2']);
        TrackingEvent::factory()->create(['event_name' => 'Purchase', 'event_id' => 'purchase-old', 'created_at' => now()->subDays(10)]);
        Order::create([
            'order_number' => 'ARM-AAA-1111', 'customer_name' => 'ক', 'customer_phone' => '01712345678', 'customer_address' => 'খ',
            'delivery_area' => 'inside', 'delivery_charge' => 150, 'subtotal' => 1000, 'grand_total' => 1150,
            'utm_source' => 'facebook', 'utm_campaign' => 'eid-offer',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.tracking.index'))
            ->assertOk()
            ->assertSee('Pixel যুক্ত')
            ->assertSee('Access token দেওয়া আছে')
            ->assertSee('Meta-তে ঘটনা যাচ্ছে')
            ->assertSee('গত ২৪ ঘণ্টায় 1টা ব্যর্থ')
            ->assertSee('৳15,500')
            ->assertSee('eid-offer')
            ->assertSee('ARM-AAA-1111')
            ->assertSee('Invalid OAuth access token')
            ->assertSee('50%');
    }

    public function test_old_event_history_is_removed(): void
    {
        TrackingEvent::factory()->create(['event_id' => 'purchase-old', 'created_at' => now()->subDays(TrackingEvent::RETENTION_DAYS + 1)]);
        $fresh = TrackingEvent::factory()->create(['event_id' => 'purchase-new']);

        $this->assertSame(1, TrackingEvent::pruneOld());
        $this->assertModelExists($fresh);
    }

    public function test_the_same_event_is_never_sent_twice(): void
    {
        $this->enableServerTracking();
        $product = Product::factory()->create();
        $this->postJson(route('landing.order'), $this->orderPayload($product))->assertOk();
        $order = Order::with('items')->firstOrFail();

        $tracker = app(MetaTracker::class);
        $tracker->purchase($order);
        $tracker->purchase($order);
        $this->app->terminate();

        $this->assertSame(1, TrackingEvent::where('event_name', 'Purchase')->count());
        $this->assertSame(1, $this->sentCount());
    }

    // ---------- the browser side ----------

    public function test_pages_load_the_shared_script_before_the_pixel_and_hand_it_the_visitor_id(): void
    {
        Setting::set('facebook_pixel_id', '1234567890');
        $product = Product::factory()->create();

        foreach (['/', route('products.show', $product->slug, false)] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $script = strpos($html, 'js/arm-core.js?v='.filemtime(public_path('js/arm-core.js')));
            $pixel = strpos($html, "fbq('init', '1234567890', advancedMatching)");

            $this->assertNotFalse($script, "arm-core.js missing on {$path}");
            $this->assertNotFalse($pixel, "Pixel init missing on {$path}");
            $this->assertLessThan($pixel, $script, "arm-core.js must come before the Pixel on {$path}");
            $this->assertStringContainsString('advancedMatching.external_id = visitorId', $html);
        }
    }

    public function test_the_product_page_reports_view_content_through_the_shared_script(): void
    {
        $product = Product::factory()->create(['price' => 15500]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee("ArmTracking.track('ViewContent'", false)
            ->assertSee('"content_ids":["'.$product->id.'"]', false);
    }

    public function test_analytics_and_clarity_load_only_with_valid_ids(): void
    {
        $this->get('/')->assertOk()->assertDontSee('googletagmanager.com')->assertDontSee('clarity.ms');

        Setting::set('google_analytics_id', 'G-ABC123XYZ9');
        Setting::set('clarity_project_id', 'abcd1234ef');
        $this->get('/')->assertOk()
            ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-ABC123XYZ9', false)
            ->assertSee("gtag('config', 'G-ABC123XYZ9')", false)
            ->assertSee('"abcd1234ef"', false);

        Setting::set('google_analytics_id', 'G-X"></script><script>alert(1)</script>');
        Setting::set('clarity_project_id', 'bad id');
        $this->get('/')->assertOk()->assertDontSee('alert(1)', false)->assertDontSee('clarity.ms');
    }

    public function test_the_analytics_ids_are_validated_in_the_settings_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/settings', ['google_analytics_id' => 'not-an-id', 'clarity_project_id' => 'BAD ID'])
            ->assertSessionHasErrors(['google_analytics_id', 'clarity_project_id']);
    }

    public function test_the_order_form_tells_visitors_their_data_may_be_shared_with_meta_for_ad_measurement(): void
    {
        $this->get('/')->assertOk()->assertSee('Meta বা Facebook-কে জানানো হতে পারে');
    }
}
