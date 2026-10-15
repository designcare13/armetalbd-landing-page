<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Support\MetaTracker;
use App\Support\ShopTime;
use App\Support\XlsxWriter;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    /** The most orders one Excel file holds, so a huge shop does not run the server out of memory. */
    private const EXPORT_LIMIT = 20000;

    /** @var array<string, string> */
    private const STATUS_LABELS = [
        'pending' => 'পেন্ডিং',
        'confirmed' => 'কনফার্মড',
        'processing' => 'প্রসেসিং',
        'shipped' => 'শিপড',
        'delivered' => 'ডেলিভার্ড',
        'cancelled' => 'বাতিল',
    ];

    public function index(Request $request)
    {
        $range = $this->dateRange($request);
        $query = $this->filteredOrders($request, $range)->with('items')->latest();

        $orders = $query->paginate($this->perPage($request))->withQueryString();

        $statusCounts = Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $summary = [
            'total' => (int) $statusCounts->sum(),
            'pending' => (int) ($statusCounts['pending'] ?? 0),
            'today' => Order::whereBetween('created_at', [ShopTime::startOfDay(), ShopTime::endOfDay()])->count(),
            'delivered_value' => (float) Order::where('status', 'delivered')->sum('grand_total'),
        ];

        // What the orders on screen add up to, so a daily or monthly sale can be read at a glance.
        $shown = $this->filteredOrders($request, $range)->toBase()->selectRaw("count(*) as total, coalesce(sum(case when status <> 'cancelled' then grand_total else 0 end), 0) as sales")->first();
        $filterSummary = ['orders' => (int) $shown->total, 'sales' => (float) $shown->sales];

        return view('admin.orders.index', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
            'summary' => $summary,
            'filterSummary' => $filterSummary,
            'dateFrom' => $range['from'],
            'dateTo' => $range['to'],
            'presets' => $this->datePresets(),
            'exportAvailable' => XlsxWriter::isAvailable(),
        ]);
    }

    /**
     * The orders on screen as an Excel file, with the same status, search and dates as the list.
     */
    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        if (! XlsxWriter::isAvailable()) {
            return back()->with('error', 'সার্ভারে Excel ফাইল বানানোর সুবিধা (PHP zip) চালু নেই। হোস্টিং সাপোর্টকে জানান।');
        }

        $range = $this->dateRange($request);
        $orders = $this->filteredOrders($request, $range)->with('items')->latest()->limit(self::EXPORT_LIMIT)->cursor();

        $rows = (function () use ($orders) {
            foreach ($orders as $order) {
                $placed = $order->created_at->inShopTime();

                yield [
                    $order->order_number,
                    $placed->format('d/m/Y'),
                    $placed->format('h:i A'),
                    $order->customer_name,
                    $order->customer_phone,
                    $order->customer_address,
                    $order->delivery_area === 'inside' ? 'ঢাকা সিটি' : 'ঢাকার বাইরে',
                    $order->items->map(fn ($item): string => "{$item->product_name} ×{$item->quantity}")->implode('; '),
                    (int) $order->items->sum('quantity'),
                    [XlsxWriter::MONEY, (float) $order->subtotal],
                    [XlsxWriter::MONEY, (float) $order->delivery_charge],
                    [XlsxWriter::MONEY, (float) $order->grand_total],
                    self::STATUS_LABELS[$order->status] ?? $order->status,
                    $order->sourceLabel(),
                    $order->order_note,
                    $order->utm_source,
                    $order->utm_campaign,
                ];
            }
        })();

        $file = XlsxWriter::create('অর্ডার', [
            'অর্ডার নং', 'তারিখ', 'সময়', 'কাস্টমারের নাম', 'ফোন', 'ঠিকানা', 'ডেলিভারি এলাকা', 'প্রোডাক্ট', 'মোট পিস',
            'সাবটোটাল (৳)', 'ডেলিভারি চার্জ (৳)', 'মোট বিল (৳)', 'স্ট্যাটাস', 'উৎস', 'নোট', 'UTM সোর্স', 'UTM ক্যাম্পেইন',
        ], $rows, [16, 12, 10, 22, 14, 38, 14, 46, 9, 14, 16, 14, 12, 18, 28, 14, 20]);

        $name = ($range['from'] || $range['to'])
            ? 'orders_'.($range['from'] ?? 'start').'_to_'.($range['to'] ?? 'now').'.xlsx'
            : 'orders-'.ShopTime::startOfDay()->inShopTime()->format('Y-m-d').'.xlsx';

        return response()->download($file, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public function show(Order $order)
    {
        $order->load('items.product');

        return view('admin.orders.show', compact('order'));
    }

    /**
     * A new order typed in by the admin, e.g. after a phone call or a Facebook message.
     */
    public function create(): View
    {
        return view('admin.orders.create', $this->formData(null));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateOrder($request, creating: true);

        try {
            $order = Order::place($this->customerData($validated), $this->orderLines($validated['items']), $validated['status']);
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }

        if ($request->boolean('send_to_meta')) {
            $tracker = app(MetaTracker::class);
            $tracker->purchase($order, [], byPhone: true);

            if ($order->status === 'confirmed') {
                $tracker->orderConfirmed($order);
            }
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', "অর্ডার {$order->order_number} সফলভাবে তৈরি করা হয়েছে।");
    }

    public function edit(Order $order): View|RedirectResponse
    {
        if (! $order->isEditable()) {
            return redirect()->route('admin.orders.show', $order)->with('error', $this->notEditableMessage($order));
        }

        return view('admin.orders.edit', $this->formData($order));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $validated = $this->validateOrder($request, creating: false);

        try {
            $order->revise($this->customerData($validated), $this->orderLines($validated['items']));
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', "অর্ডার {$order->order_number} সফলভাবে আপডেট করা হয়েছে।");
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
        ]);

        $previousStatus = $order->status;
        $wasCancelled = $order->status === 'cancelled';
        $isCancelled = $request->status === 'cancelled';

        DB::transaction(function () use ($order, $request, $wasCancelled, $isCancelled): void {
            if ($isCancelled && ! $wasCancelled) {
                $this->restoreStock($order);
            } elseif ($wasCancelled && ! $isCancelled) {
                $this->deductStock($order);
            }

            $order->update(['status' => $request->status]);
        });

        if ($request->status === 'confirmed' && $previousStatus !== 'confirmed') {
            app(MetaTracker::class)->orderConfirmed($order->loadMissing('items'));
        }

        $labels = [
            'pending' => 'পেন্ডিং',
            'confirmed' => 'কনফার্মড',
            'processing' => 'প্রসেসিং',
            'shipped' => 'শিপড',
            'delivered' => 'ডেলিভারড',
            'cancelled' => 'বাতিল',
        ];

        return back()->with('success', "অর্ডারের স্ট্যাটাস সফলভাবে '{$labels[$request->status]}' হিসেবে আপডেট করা হয়েছে।");
    }

    public function destroy(Order $order)
    {
        DB::transaction(function () use ($order): void {
            if ($order->status !== 'cancelled') {
                $this->restoreStock($order);
            }

            $order->delete();
        });

        return redirect()->route('admin.orders.index')->with('success', 'অর্ডার সফলভাবে মুছে ফেলা হয়েছে।');
    }

    /**
     * The status, search and date filters of the list, shared by the screen and the Excel file.
     *
     * @param  array{from: string|null, to: string|null}  $range
     * @return Builder<Order>
     */
    private function filteredOrders(Request $request, array $range): Builder
    {
        $query = Order::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // A day means a day in Bangladesh, which does not line up with the UTC days the dates are stored in.
        if ($range['from']) {
            $query->where('created_at', '>=', ShopTime::startOfDay($range['from']));
        }

        if ($range['to']) {
            $query->where('created_at', '<=', ShopTime::endOfDay($range['to']));
        }

        return $query;
    }

    /**
     * The "from" and "to" days (Y-m-d) of the list. A bad value is rejected, and a "to" before the "from" is not allowed.
     *
     * @return array{from: string|null, to: string|null}
     */
    private function dateRange(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ], [
            'from.date_format' => 'শুরুর তারিখটি সঠিক নয়।',
            'to.date_format' => 'শেষের তারিখটি সঠিক নয়।',
            'to.after_or_equal' => 'শেষের তারিখ শুরুর তারিখের আগে হতে পারবে না।',
        ]);

        return ['from' => $validated['from'] ?? null, 'to' => $validated['to'] ?? null];
    }

    /**
     * Quick date choices for the list: label => [from, to].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    private function datePresets(): array
    {
        $today = ShopTime::startOfDay()->inShopTime();

        return [
            'আজ' => [$today->toDateString(), $today->toDateString()],
            'গতকাল' => [$today->copy()->subDay()->toDateString(), $today->copy()->subDay()->toDateString()],
            'গত ৭ দিন' => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
            'এই মাস' => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
            'গত মাস' => [$today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        ];
    }

    private function notEditableMessage(Order $order): string
    {
        return in_array($order->status, Order::EDITABLE_STATUSES, true)
            ? 'এই অর্ডারের কোনো প্রোডাক্ট মুছে ফেলা হয়েছে, তাই অর্ডারটি এডিট করা যাবে না।'
            : 'ডেলিভারি সম্পন্ন বা বাতিল হওয়া অর্ডার এডিট করা যায় না।';
    }

    /**
     * What the order form needs: the products to pick from, the delivery charges and the starting lines.
     *
     * @return array<string, mixed>
     */
    private function formData(?Order $order): array
    {
        $settings = Setting::allWithDefaults();
        $orderedIds = $order?->items->pluck('product_id')->all() ?? [];

        $products = Product::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $orderedIds))
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'stock', 'is_active']);

        $keptQuantity = $order ? $order->items->groupBy('product_id')->map(fn ($items) => (int) $items->sum('quantity')) : collect();

        return [
            'order' => $order,
            'products' => $products->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name.($product->is_active ? '' : ' (বন্ধ আছে)'),
                'price' => (float) $product->price,
                // The pieces already in this order go back to stock when it is saved, so they count as available.
                'stock' => $product->stock + (int) ($keptQuantity[$product->id] ?? 0),
            ])->values()->all(),
            'deliveryCharges' => [
                'inside' => (float) $settings['delivery_inside'],
                'outside' => (float) $settings['delivery_outside'],
            ],
            'startingLines' => $order
                ? $order->items->map(fn ($item): array => ['id' => $item->product_id, 'qty' => $item->quantity, 'price' => (float) $item->unit_price])->values()->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateOrder(Request $request, bool $creating): array
    {
        $request->merge(['customer_phone' => Order::normalizePhone((string) $request->input('customer_phone'))]);

        return $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'customer_address' => ['required', 'string', 'max:1000'],
            'delivery_area' => ['required', Rule::in(['inside', 'outside'])],
            'delivery_charge' => ['required', 'numeric', 'min:0', 'max:99999'],
            'order_note' => ['nullable', 'string', 'max:500'],
            'source' => ['required', Rule::in(array_keys(Order::SOURCES))],
            'status' => $creating ? ['required', Rule::in(['pending', 'confirmed'])] : ['sometimes'],
            'send_to_meta' => ['sometimes', 'boolean'],
            'items' => ['required', 'array', 'max:20'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:0', 'max:50'],
            'items.*.price' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ], [
            'customer_name.required' => 'কাস্টমারের নাম লিখুন।',
            'customer_phone.required' => 'মোবাইল নম্বর লিখুন।',
            'customer_phone.regex' => '১১ ডিজিটের সঠিক মোবাইল নম্বর দিন (যেমন: 01805962220)।',
            'customer_address.required' => 'সম্পূর্ণ ঠিকানা লিখুন।',
            'delivery_charge.required' => 'ডেলিভারি চার্জ লিখুন (না থাকলে ০)।',
            'items.required' => 'অন্তত একটি প্রোডাক্ট যোগ করুন।',
            'items.*.id.exists' => 'নির্বাচিত প্রোডাক্টটি পাওয়া যায়নি।',
            'items.*.price.required' => 'প্রতিটি প্রোডাক্টের দাম লিখুন।',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function customerData(array $validated): array
    {
        return Arr::only($validated, ['customer_name', 'customer_phone', 'customer_address', 'delivery_area', 'delivery_charge', 'order_note', 'source']);
    }

    /**
     * Lines with a quantity of 0 are dropped and the same product picked twice becomes one line.
     *
     * @param  array<int, array{id: int|string, qty: int|string, price: int|float|string}>  $items
     * @return array<int, array{id: int, qty: int, price: float}>
     *
     * @throws ValidationException when nothing is left
     */
    private function orderLines(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            if ((int) $item['qty'] < 1) {
                continue;
            }

            $id = (int) $item['id'];
            $lines[$id] = ['id' => $id, 'qty' => ($lines[$id]['qty'] ?? 0) + (int) $item['qty'], 'price' => $lines[$id]['price'] ?? (float) $item['price']];
        }

        if ($lines === []) {
            throw ValidationException::withMessages(['items' => 'অন্তত একটি প্রোডাক্টের পরিমাণ ১ বা তার বেশি দিন।']);
        }

        return array_values($lines);
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $item->product?->increment('stock', $item->quantity);
        }
    }

    private function deductStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $item->product?->update(['stock' => max(0, $item->product->stock - $item->quantity)]);
        }
    }
}
