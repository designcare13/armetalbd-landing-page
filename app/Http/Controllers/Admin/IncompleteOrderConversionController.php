<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IncompleteOrder;
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

/**
 * Turns a saved incomplete order into a real order after the customer confirmed it by phone.
 */
class IncompleteOrderConversionController extends Controller
{
    private const MAX_QTY_PER_ITEM = 50;

    public function create(IncompleteOrder $incompleteOrder): View
    {
        $settings = Setting::allWithDefaults();

        return view('admin.incomplete-orders.convert', [
            'incompleteOrder' => $incompleteOrder,
            'lines' => $this->cartLines($incompleteOrder),
            'deliveryCharges' => [
                'inside' => (float) $settings['delivery_inside'],
                'outside' => (float) $settings['delivery_outside'],
            ],
            'earlierOrders' => Order::where('customer_phone', $incompleteOrder->customer_phone)->latest()->take(3)->get(),
        ]);
    }

    public function store(Request $request, IncompleteOrder $incompleteOrder): RedirectResponse
    {
        $request->merge(['customer_phone' => Order::normalizePhone((string) $request->input('customer_phone'))]);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'customer_address' => ['required', 'string', 'max:1000'],
            'delivery_area' => ['required', Rule::in(['inside', 'outside'])],
            'order_note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'max:20'],
            'items.*.id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:0', 'max:'.self::MAX_QTY_PER_ITEM],
        ], [
            'customer_name.required' => 'কাস্টমারের নাম লিখুন।',
            'customer_phone.regex' => '১১ ডিজিটের সঠিক মোবাইল নম্বর দিন।',
            'customer_address.required' => 'সম্পূর্ণ ঠিকানা লিখুন।',
        ]);

        $lines = array_values(array_filter($validated['items'], fn (array $item): bool => (int) $item['qty'] > 0));

        if ($lines === []) {
            throw ValidationException::withMessages(['items' => 'অন্তত একটি প্রোডাক্টের পরিমাণ ১ বা তার বেশি দিন।']);
        }

        try {
            $order = DB::transaction(function () use ($validated, $lines, $incompleteOrder): Order {
                // The customer already confirmed by phone, so the order starts as confirmed.
                $order = Order::place($validated, $lines, 'confirmed', Arr::only($incompleteOrder->getAttributes(), Order::ATTRIBUTION_KEYS));

                IncompleteOrder::discardForOrder($incompleteOrder->visitor_token, $validated['customer_phone']);
                $incompleteOrder->delete();

                return $order;
            });
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }

        // The sale happened on the phone, so there is no browser event: Meta only hears about it from here.
        $tracker = app(MetaTracker::class);
        $tracker->purchase($order, [], byPhone: true);
        $tracker->orderConfirmed($order);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', "অর্ডার {$order->order_number} সফলভাবে তৈরি হয়েছে এবং কনফার্মড করা হয়েছে।");
    }

    /**
     * The saved cart with today's price and stock, so the admin confirms what can really be ordered.
     *
     * @return array<int, array{id: int, name: string, price: float, stock: int, qty: int, max: int, available: bool, reason: string|null}>
     */
    private function cartLines(IncompleteOrder $incompleteOrder): array
    {
        $savedItems = collect($incompleteOrder->items ?? []);
        $products = Product::whereIn('id', $savedItems->pluck('product_id'))->get()->keyBy('id');

        return $savedItems->map(function (array $saved) use ($products): array {
            $product = $products->get($saved['product_id']);

            $reason = match (true) {
                $product === null => 'প্রোডাক্টটি আর নেই',
                ! $product->is_active => 'প্রোডাক্টটি এখন বন্ধ আছে',
                $product->stock < 1 => 'স্টক শেষ',
                default => null,
            };
            $max = $product ? max(0, min($product->stock, self::MAX_QTY_PER_ITEM)) : 0;

            return [
                'id' => (int) $saved['product_id'],
                'name' => $product->name ?? $saved['name'],
                'price' => (float) ($product->price ?? $saved['price']),
                'stock' => (int) ($product->stock ?? 0),
                'qty' => min((int) $saved['qty'], $max),
                'max' => $max,
                'available' => $reason === null,
                'reason' => $reason,
            ];
        })->all();
    }
}
