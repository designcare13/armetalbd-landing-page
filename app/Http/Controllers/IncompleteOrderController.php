<?php

namespace App\Http\Controllers;

use App\Models\IncompleteOrder;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class IncompleteOrderController extends Controller
{
    /**
     * Save (or refresh) the details a visitor has typed so far. Called by the landing page
     * once a valid phone number is entered; one row is kept per browser.
     */
    public function store(Request $request): Response
    {
        $request->merge(['customer_phone' => Order::normalizePhone((string) $request->input('customer_phone'))]);

        $validated = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'customer_phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_address' => ['nullable', 'string', 'max:1000'],
            'delivery_area' => ['nullable', Rule::in(['inside', 'outside'])],
            'items' => ['nullable', 'array', 'max:20'],
            'items.*.id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        // A capture request can still be in flight when the order is submitted; do not bring the entry back.
        $orderedJustNow = Order::where('customer_phone', $validated['customer_phone'])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($orderedJustNow) {
            return response()->noContent();
        }

        $items = $this->cartItems($validated['items'] ?? []);

        $incompleteOrder = IncompleteOrder::firstOrNew(['visitor_token' => $validated['visitor_token']]);
        $incompleteOrder->fill([
            'customer_phone' => $validated['customer_phone'],
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_address' => $validated['customer_address'] ?? null,
            'delivery_area' => $validated['delivery_area'] ?? null,
            'items' => $items,
            'cart_total' => collect($items)->sum(fn (array $item): float => $item['price'] * $item['qty']),
            'last_captured_at' => now(),
        ])->save();

        // Cleanup runs here so the retention promise does not depend on a server cron job.
        if (random_int(1, 50) === 1) {
            IncompleteOrder::pruneExpired();
        }

        return response()->noContent();
    }

    /**
     * Rebuild the cart from the database so names and prices cannot be forged from the browser.
     *
     * @param  array<int, array{id: int|string, qty: int|string}>  $requestedItems
     * @return array<int, array{product_id: int, name: string, price: float, qty: int}>
     */
    private function cartItems(array $requestedItems): array
    {
        $quantities = collect($requestedItems)->mapWithKeys(fn (array $item): array => [(int) $item['id'] => (int) $item['qty']]);

        return Product::where('is_active', true)
            ->whereIn('id', $quantities->keys())
            ->get()
            ->map(fn (Product $product): array => [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'qty' => $quantities[$product->id],
            ])
            ->values()
            ->all();
    }
}
