<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Support\XlsxWriter;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use ZipArchive;

class OrderDateFilterAndExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * An order placed at the given UTC moment, which is how dates are stored.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function orderAt(string $utc, array $overrides = []): Order
    {
        static $counter = 0;
        $counter++;

        $order = Order::create(array_merge([
            'order_number' => 'ARM-DATE-'.$counter,
            'customer_name' => "কাস্টমার {$counter}",
            'customer_phone' => '01805962220',
            'customer_address' => 'মিরপুর, ঢাকা',
            'delivery_area' => 'inside',
            'delivery_charge' => 150,
            'subtotal' => 1000,
            'grand_total' => 1150,
            'status' => 'pending',
        ], $overrides));
        $order->forceFill(['created_at' => $utc, 'updated_at' => $utc])->saveQuietly();

        return $order;
    }

    /**
     * @return array<int, string>
     */
    private function listedNumbers(array $query = []): array
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', $query))->assertOk();

        return $response->viewData('orders')->pluck('order_number')->sort()->values()->all();
    }

    // ---------- Bangladesh time ----------

    public function test_order_times_are_shown_in_bangladesh_time(): void
    {
        $order = $this->orderAt('2026-10-15 20:30:00');

        $this->actingAs($this->admin)->get(route('admin.orders.index'))->assertOk()->assertSee('16 Oct 2026')->assertSee('02:30 AM');
        $this->actingAs($this->admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee('16 Oct 2026, 02:30 AM');
    }

    public function test_a_day_starts_and_ends_at_bangladesh_midnight(): void
    {
        $before = $this->orderAt('2026-10-14 17:59:59'); // 23:59:59 on 14 Oct in Dhaka
        $start = $this->orderAt('2026-10-14 18:00:00');  // 00:00:00 on 15 Oct in Dhaka
        $end = $this->orderAt('2026-10-15 17:59:59');    // 23:59:59 on 15 Oct in Dhaka
        $after = $this->orderAt('2026-10-15 18:00:00');  // 00:00:00 on 16 Oct in Dhaka

        $this->assertSame([$start->order_number, $end->order_number], $this->listedNumbers(['from' => '2026-10-15', 'to' => '2026-10-15']));
        $this->assertContains($before->order_number, $this->listedNumbers(['to' => '2026-10-14']));
        $this->assertNotContains($start->order_number, $this->listedNumbers(['to' => '2026-10-14']));
        $this->assertSame([$after->order_number], $this->listedNumbers(['from' => '2026-10-16']));
    }

    public function test_only_a_from_date_or_only_a_to_date_works(): void
    {
        $old = $this->orderAt('2026-09-01 08:00:00');
        $middle = $this->orderAt('2026-10-10 08:00:00');
        $new = $this->orderAt('2026-10-20 08:00:00');

        $this->assertSame([$middle->order_number, $new->order_number], $this->listedNumbers(['from' => '2026-10-01']));
        $this->assertSame([$old->order_number, $middle->order_number], $this->listedNumbers(['to' => '2026-10-15']));
        $this->assertCount(3, $this->listedNumbers());
    }

    public function test_dates_work_together_with_status_and_search(): void
    {
        $match = $this->orderAt('2026-10-10 08:00:00', ['status' => 'confirmed', 'customer_name' => 'রাসেল']);
        $this->orderAt('2026-10-10 08:00:00', ['status' => 'pending', 'customer_name' => 'রাসেল']);
        $this->orderAt('2026-10-10 08:00:00', ['status' => 'confirmed', 'customer_name' => 'করিম']);
        $this->orderAt('2026-08-10 08:00:00', ['status' => 'confirmed', 'customer_name' => 'রাসেল']);

        $this->assertSame([$match->order_number], $this->listedNumbers(['status' => 'confirmed', 'search' => 'রাসেল', 'from' => '2026-10-01', 'to' => '2026-10-31']));
    }

    public function test_bad_or_backwards_dates_are_refused(): void
    {
        $this->orderAt('2026-10-10 08:00:00');

        $this->actingAs($this->admin)->get(route('admin.orders.index', ['from' => 'yesterday']))->assertSessionHasErrors('from');
        $this->actingAs($this->admin)->get(route('admin.orders.index', ['from' => '2026-13-45']))->assertSessionHasErrors('from');
        $this->actingAs($this->admin)->get(route('admin.orders.index', ['from' => '2026-10-20', 'to' => '2026-10-10']))->assertSessionHasErrors('to');
        $this->actingAs($this->admin)->get(route('admin.orders.export', ['from' => 'bad']))->assertSessionHasErrors('from');
    }

    public function test_the_list_shows_what_the_filtered_orders_add_up_to_without_cancelled_ones(): void
    {
        $this->orderAt('2026-10-10 08:00:00', ['grand_total' => 1000]);
        $this->orderAt('2026-10-11 08:00:00', ['grand_total' => 2500, 'status' => 'delivered']);
        $this->orderAt('2026-10-12 08:00:00', ['grand_total' => 9999, 'status' => 'cancelled']);
        $this->orderAt('2026-07-12 08:00:00', ['grand_total' => 50000]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))->assertOk();

        $this->assertSame(['orders' => 3, 'sales' => 3500.0], $response->viewData('filterSummary'));
        $response->assertSee('৳3,500');
    }

    public function test_paging_and_the_status_filters_keep_the_dates(): void
    {
        foreach (range(1, 20) as $day) {
            $this->orderAt('2026-10-10 08:00:00');
        }

        $this->actingAs($this->admin)->get(route('admin.orders.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('from=2026-10-01&amp;to=2026-10-31&amp;page=2', false)
            ->assertSee('status=pending&amp;from=2026-10-01&amp;to=2026-10-31', false)
            ->assertSee('name="from" id="from" value="2026-10-01"', false);
    }

    public function test_quick_date_buttons_use_the_bangladesh_calendar(): void
    {
        // 19:00 UTC on 31 Mar is already 1 April in Dhaka.
        Carbon::setTestNow('2026-03-31 19:00:00');

        $presets = $this->actingAs($this->admin)->get(route('admin.orders.index'))->assertOk()->viewData('presets');

        $this->assertSame(['2026-04-01', '2026-04-01'], $presets['আজ']);
        $this->assertSame(['2026-03-31', '2026-03-31'], $presets['গতকাল']);
        $this->assertSame(['2026-03-26', '2026-04-01'], $presets['গত ৭ দিন']);
        $this->assertSame(['2026-04-01', '2026-04-01'], $presets['এই মাস']);
        $this->assertSame(['2026-03-01', '2026-03-31'], $presets['গত মাস']);
    }

    public function test_last_month_does_not_overflow_on_the_31st(): void
    {
        Carbon::setTestNow('2026-03-31 06:00:00');

        $presets = $this->actingAs($this->admin)->get(route('admin.orders.index'))->viewData('presets');

        $this->assertSame(['2026-02-01', '2026-02-28'], $presets['গত মাস']);
    }

    public function test_todays_numbers_follow_the_shop_day(): void
    {
        // 19:00 UTC is 01:00 the next day in Dhaka, so the Dhaka "today" began at 18:00 UTC.
        Carbon::setTestNow('2026-10-15 19:00:00');
        $this->orderAt('2026-10-15 18:30:00', ['grand_total' => 700]);
        $this->orderAt('2026-10-15 17:00:00', ['grand_total' => 300]);

        $this->assertSame(1, $this->actingAs($this->admin)->get(route('admin.orders.index'))->viewData('summary')['today']);
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();
    }

    // ---------- Excel file ----------

    /**
     * @return array{zip: ZipArchive, sheet: DOMDocument, rows: array<int, array<int, string>>, path: string}
     */
    private function openExport(array $query = []): array
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.export', $query))->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $copy = tempnam(sys_get_temp_dir(), 'export-copy').'.xlsx';
        copy($path, $copy);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($copy));

        $sheet = new DOMDocument;
        $this->assertTrue($sheet->loadXML((string) $zip->getFromName('xl/worksheets/sheet1.xml')), 'the sheet is well-formed XML');

        $rows = [];
        foreach ($sheet->getElementsByTagName('row') as $row) {
            $cells = [];
            foreach ($row->getElementsByTagName('c') as $cell) {
                $cells[preg_replace('/\d+/', '', $cell->getAttribute('r'))] = $cell->textContent;
            }
            $rows[(int) $row->getAttribute('r')] = $cells;
        }

        return ['zip' => $zip, 'sheet' => $sheet, 'rows' => $rows, 'path' => $copy];
    }

    public function test_the_export_is_a_real_xlsx_download(): void
    {
        $this->orderAt('2026-10-10 08:00:00');

        $response = $this->actingAs($this->admin)->get(route('admin.orders.export'))->assertOk();

        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('attachment; filename=orders-', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringEndsWith('.xlsx', (string) $response->headers->get('Content-Disposition'));

        $export = $this->openExport();
        foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/styles.xml', 'xl/worksheets/sheet1.xml'] as $part) {
            $this->assertNotFalse($export['zip']->getFromName($part), "{$part} is in the file");
            $xml = new DOMDocument;
            $this->assertTrue($xml->loadXML((string) $export['zip']->getFromName($part)), "{$part} is well-formed");
        }
    }

    public function test_the_export_has_a_header_row_and_one_row_per_order_with_the_details(): void
    {
        $order = $this->orderAt('2026-10-15 20:30:00', [
            'customer_name' => 'রাসেল হোসাইন',
            'customer_phone' => '01805962220',
            'customer_address' => 'বাড়ি ১২, মিরপুর',
            'delivery_area' => 'outside',
            'delivery_charge' => 300,
            'subtotal' => 28000,
            'grand_total' => 28300,
            'status' => 'confirmed',
            'source' => 'facebook',
            'order_note' => 'সন্ধ্যায় কল দিন',
            'utm_source' => 'facebook',
            'utm_campaign' => 'eid-sale',
        ]);
        $order->items()->createMany([
            ['product_name' => 'দোলনা চেয়ার', 'unit_price' => 14000, 'quantity' => 2, 'line_total' => 28000],
            ['product_name' => 'বেবি কট', 'unit_price' => 500, 'quantity' => 1, 'line_total' => 500],
        ]);

        $rows = $this->openExport()['rows'];

        $this->assertCount(2, $rows);
        $this->assertSame('অর্ডার নং', $rows[1]['A']);
        $this->assertSame('মোট বিল (৳)', $rows[1]['L']);

        $this->assertSame($order->order_number, $rows[2]['A']);
        $this->assertSame('16/10/2026', $rows[2]['B']);
        $this->assertSame('02:30 AM', $rows[2]['C']);
        $this->assertSame('রাসেল হোসাইন', $rows[2]['D']);
        $this->assertSame('01805962220', $rows[2]['E'], 'the leading zero of the phone number is kept');
        $this->assertSame('বাড়ি ১২, মিরপুর', $rows[2]['F']);
        $this->assertSame('ঢাকার বাইরে', $rows[2]['G']);
        $this->assertSame('দোলনা চেয়ার ×2; বেবি কট ×1', $rows[2]['H']);
        $this->assertSame('3', $rows[2]['I']);
        $this->assertSame('28000', $rows[2]['J']);
        $this->assertSame('300', $rows[2]['K']);
        $this->assertSame('28300', $rows[2]['L']);
        $this->assertSame('কনফার্মড', $rows[2]['M']);
        $this->assertSame('ফেসবুক / মেসেঞ্জার', $rows[2]['N']);
        $this->assertSame('সন্ধ্যায় কল দিন', $rows[2]['O']);
        $this->assertSame('eid-sale', $rows[2]['Q']);
    }

    public function test_the_phone_number_is_stored_as_text_and_money_as_numbers(): void
    {
        $this->orderAt('2026-10-10 08:00:00');

        $xml = (string) $this->openExport()['zip']->getFromName('xl/worksheets/sheet1.xml');

        $this->assertMatchesRegularExpression('#<c r="E2" t="inlineStr"><is><t xml:space="preserve">01805962220</t></is></c>#', $xml);
        $this->assertMatchesRegularExpression('#<c r="L2" s="2"><v>1150</v></c>#', $xml);
    }

    public function test_the_export_follows_the_same_filters_as_the_list(): void
    {
        $this->orderAt('2026-10-10 08:00:00', ['status' => 'confirmed', 'customer_name' => 'রাসেল']);
        $this->orderAt('2026-10-10 08:00:00', ['status' => 'pending', 'customer_name' => 'রাসেল']);
        $this->orderAt('2026-08-10 08:00:00', ['status' => 'confirmed', 'customer_name' => 'রাসেল']);

        $names = collect($this->openExport(['status' => 'confirmed', 'from' => '2026-10-01', 'to' => '2026-10-31', 'search' => 'রাসেল'])['rows'])->slice(1)->pluck('M')->all();
        $this->assertSame(['কনফার্মড'], $names);

        $this->assertCount(4, $this->openExport()['rows'], 'no filter gives every order plus the header');
    }

    public function test_awkward_text_cannot_break_the_file(): void
    {
        $this->orderAt('2026-10-10 08:00:00', [
            'customer_name' => 'A & B <script>"quoted" \'single\'',
            'customer_address' => "লাইন এক\nলাইন দুই\x01\x08 শেষ",
        ]);

        $rows = $this->openExport()['rows'];

        $this->assertSame('A & B <script>"quoted" \'single\'', $rows[2]['D']);
        $this->assertSame("লাইন এক\nলাইন দুই শেষ", $rows[2]['F'], 'forbidden control characters are dropped');
    }

    public function test_an_empty_result_still_gives_a_file_with_only_the_header(): void
    {
        $this->orderAt('2026-10-10 08:00:00');

        $this->assertCount(1, $this->openExport(['from' => '2027-01-01'])['rows']);
    }

    public function test_the_list_offers_the_export_with_the_current_filters(): void
    {
        $this->orderAt('2026-10-10 08:00:00', ['status' => 'confirmed']);

        $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'confirmed', 'from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('Excel ডাউনলোড')
            ->assertSee(e(route('admin.orders.export', ['status' => 'confirmed', 'from' => '2026-10-01', 'to' => '2026-10-31'])), false);
    }

    public function test_guests_cannot_download_the_orders(): void
    {
        $this->orderAt('2026-10-10 08:00:00');

        $this->get(route('admin.orders.export'))->assertRedirect('/login');
    }

    public function test_columns_beyond_z_get_double_letter_names(): void
    {
        $headers = array_map(fn (int $number): string => "H{$number}", range(1, 28));
        $path = XlsxWriter::create('Wide', $headers, [array_map(fn (int $number): int => $number, range(1, 28))]);

        $zip = new ZipArchive;
        $zip->open($path);
        $xml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($path);

        $this->assertStringContainsString('r="Z1"', $xml);
        $this->assertStringContainsString('r="AA1"', $xml);
        $this->assertStringContainsString('r="AB2"', $xml);
    }

    public function test_the_file_is_named_after_the_dates_it_covers(): void
    {
        $this->orderAt('2026-10-10 08:00:00');
        Carbon::setTestNow('2026-10-20 06:00:00');

        $this->actingAs($this->admin)->get(route('admin.orders.export', ['from' => '2026-10-01', 'to' => '2026-10-15']))
            ->assertHeader('Content-Disposition', 'attachment; filename=orders_2026-10-01_to_2026-10-15.xlsx');
        $this->actingAs($this->admin)->get(route('admin.orders.export', ['from' => '2026-10-01']))
            ->assertHeader('Content-Disposition', 'attachment; filename=orders_2026-10-01_to_now.xlsx');
        $this->actingAs($this->admin)->get(route('admin.orders.export'))
            ->assertHeader('Content-Disposition', 'attachment; filename=orders-2026-10-20.xlsx');
    }
}
