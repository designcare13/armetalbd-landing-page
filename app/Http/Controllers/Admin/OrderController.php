<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Support\MetaTracker;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('items')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate($this->perPage($request))->withQueryString();

        $statusCounts = Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $summary = [
            'total' => (int) $statusCounts->sum(),
            'pending' => (int) ($statusCounts['pending'] ?? 0),
            'today' => Order::whereDate('created_at', today())->count(),
            'delivered_value' => (float) Order::where('status', 'delivered')->sum('grand_total'),
        ];

        return view('admin.orders.index', compact('orders', 'statusCounts', 'summary'));
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
