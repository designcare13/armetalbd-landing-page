<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    /** Where the customer came from and the ids Meta needs to match the order to an ad click. */
    public const ATTRIBUTION_KEYS = ['visitor_token', 'fbp', 'fbc', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'referrer'];

    /** Where an order came from; the website sets "website", the admin picks one when entering an order by hand. */
    public const SOURCES = [
        'website' => 'ওয়েবসাইট',
        'phone' => 'ফোন কল',
        'facebook' => 'ফেসবুক / মেসেঞ্জার',
        'whatsapp' => 'হোয়াটসঅ্যাপ',
        'showroom' => 'শোরুম',
    ];

    /** Orders in these states can still be changed; delivered and cancelled ones are final. */
    public const EDITABLE_STATUSES = ['pending', 'confirmed', 'processing', 'shipped'];

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_address',
        'delivery_area',
        'delivery_charge',
        'subtotal',
        'grand_total',
        'payment_method',
        'order_note',
        'status',
        'source',
        'visitor_token',
        'fbp',
        'fbc',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'referrer',
    ];

    protected $casts = [
        'delivery_charge' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    /**
     * Accept Bangla digits, spaces, dashes and +88 / 88 prefixes in phone numbers.
     */
    public static function normalizePhone(string $phone): string
    {
        $phone = strtr($phone, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']);
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            return '0'.substr($digits, 3);
        }

        return $digits;
    }

    /**
     * Create an order and take its stock in one transaction. The storefront, the admin "new order" form and
     * the admin "convert incomplete order" action all go through here so the rules stay identical.
     *
     * Optional extras, used only by the admin forms: a custom "delivery_charge" and a "source" in $customer,
     * and a custom "price" on a line. The storefront never sends them, so it always uses the settings and the product price.
     *
     * @param  array<string, mixed>  $customer  customer_name, customer_phone, customer_address, delivery_area, order_note
     * @param  array<int, array{id: int|string, qty: int|string, price?: int|float|string}>  $lines
     * @param  array<string, string|null>  $attribution  visitor_token, fbp, fbc, utm_* and referrer; unknown keys are ignored
     *
     * @throws DomainException when stock is short or no line refers to an active product
     */
    public static function place(array $customer, array $lines, string $status = 'pending', array $attribution = []): self
    {
        $deliveryCharge = self::deliveryChargeFor($customer);

        return DB::transaction(function () use ($customer, $lines, $status, $attribution, $deliveryCharge): self {
            ['items' => $items, 'subtotal' => $subtotal] = self::takeStock($lines);

            do {
                $orderNumber = 'ARM-'.strtoupper(Str::random(3)).'-'.random_int(1000, 9999);
            } while (self::where('order_number', $orderNumber)->exists());

            $order = self::create(Arr::only($attribution, self::ATTRIBUTION_KEYS) + [
                'order_number' => $orderNumber,
                'customer_name' => $customer['customer_name'],
                'customer_phone' => $customer['customer_phone'],
                'customer_address' => $customer['customer_address'],
                'delivery_area' => $customer['delivery_area'],
                'delivery_charge' => $deliveryCharge,
                'subtotal' => $subtotal,
                'grand_total' => $subtotal + $deliveryCharge,
                'payment_method' => 'cod',
                'order_note' => $customer['order_note'] ?? null,
                'status' => $status,
                'source' => $customer['source'] ?? 'website',
            ]);

            $order->items()->createMany($items);

            return $order->load('items');
        });
    }

    /**
     * Change an existing order: its customer details, delivery and items. The stock of the old items goes
     * back and the new items are taken in the same transaction, so the stock always matches the order.
     *
     * @param  array<string, mixed>  $customer  same keys as place(); "source" is optional
     * @param  array<int, array{id: int|string, qty: int|string, price?: int|float|string}>  $lines
     *
     * @throws DomainException when the order can no longer be changed or stock is short
     */
    public function revise(array $customer, array $lines): self
    {
        $deliveryCharge = self::deliveryChargeFor($customer);

        return DB::transaction(function () use ($customer, $lines, $deliveryCharge): self {
            $order = self::with('items')->lockForUpdate()->findOrFail($this->getKey());

            if (! $order->isEditable()) {
                throw new DomainException('এই অর্ডারটি আর এডিট করা যাবে না।');
            }

            foreach ($order->items->sortBy('product_id') as $item) {
                Product::lockForUpdate()->find($item->product_id)?->increment('stock', $item->quantity);
            }

            // A product that was switched off after the order was placed may stay in it.
            ['items' => $items, 'subtotal' => $subtotal] = self::takeStock($lines, $order->items->pluck('product_id')->all());

            $order->items()->delete();
            $order->items()->createMany($items);
            $order->update([
                'customer_name' => $customer['customer_name'],
                'customer_phone' => $customer['customer_phone'],
                'customer_address' => $customer['customer_address'],
                'delivery_area' => $customer['delivery_area'],
                'delivery_charge' => $deliveryCharge,
                'subtotal' => $subtotal,
                'grand_total' => $subtotal + $deliveryCharge,
                'order_note' => $customer['order_note'] ?? null,
                'source' => $customer['source'] ?? $order->source,
            ]);

            return $order->load('items');
        });
    }

    /**
     * Whether the admin may still change this order. An item whose product was deleted cannot be put
     * back into stock or priced again, so such an order stays as it is.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true)
            && $this->loadMissing('items')->items->every(fn (OrderItem $item): bool => $item->product_id !== null);
    }

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->source] ?? self::SOURCES['website'];
    }

    /**
     * @param  array<string, mixed>  $customer
     */
    private static function deliveryChargeFor(array $customer): float
    {
        if (isset($customer['delivery_charge']) && $customer['delivery_charge'] !== '') {
            return (float) $customer['delivery_charge'];
        }

        $settings = Setting::allWithDefaults();

        return (float) ($customer['delivery_area'] === 'inside' ? $settings['delivery_inside'] : $settings['delivery_outside']);
    }

    /**
     * Lock the products of the lines, check the stock, take it, and return the order item rows with their subtotal.
     *
     * @param  array<int, array{id: int|string, qty: int|string, price?: int|float|string}>  $lines
     * @param  array<int, int|null>  $alsoAllowedProductIds  inactive products that may still be used
     * @return array{items: array<int, array<string, mixed>>, subtotal: float|int}
     *
     * @throws DomainException when stock is short or no line refers to a usable product
     */
    private static function takeStock(array $lines, array $alsoAllowedProductIds = []): array
    {
        $subtotal = 0;
        $items = [];

        foreach ($lines as $line) {
            $product = Product::where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', array_filter($alsoAllowedProductIds)))
                ->lockForUpdate()
                ->find($line['id']);

            if (! $product) {
                continue;
            }

            $qty = (int) $line['qty'];

            if ($product->stock < $qty) {
                throw new DomainException($product->stock > 0
                    ? "দুঃখিত, '{$product->name}' এর মাত্র {$product->stock} টি স্টকে আছে।"
                    : "দুঃখিত, '{$product->name}' এখন স্টকে নেই।");
            }

            $product->decrement('stock', $qty);
            $price = isset($line['price']) && $line['price'] !== '' ? (float) $line['price'] : (float) $product->price;
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_image' => $product->image_url,
                'unit_price' => $price,
                'quantity' => $qty,
                'line_total' => $lineTotal,
            ];
        }

        if ($items === []) {
            throw new DomainException('নির্বাচিত কোনো ভ্যালিড প্রোডাক্ট পাওয়া যায়নি।');
        }

        return ['items' => $items, 'subtotal' => $subtotal];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
