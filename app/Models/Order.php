<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

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
     * Create an order and take its stock in one transaction. The storefront and the admin
     * "convert incomplete order" action both go through here so the rules stay identical.
     *
     * @param  array<string, mixed>  $customer  customer_name, customer_phone, customer_address, delivery_area, order_note
     * @param  array<int, array{id: int|string, qty: int|string}>  $lines
     *
     * @throws DomainException when stock is short or no line refers to an active product
     */
    public static function place(array $customer, array $lines, string $status = 'pending'): self
    {
        $settings = Setting::allWithDefaults();
        $deliveryCharge = (float) ($customer['delivery_area'] === 'inside' ? $settings['delivery_inside'] : $settings['delivery_outside']);

        return DB::transaction(function () use ($customer, $lines, $status, $deliveryCharge): self {
            $subtotal = 0;
            $items = [];

            foreach ($lines as $line) {
                $product = Product::where('is_active', true)->lockForUpdate()->find($line['id']);
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
                $price = (float) $product->price;
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
                throw new DomainException('নির্বাচিত কোনো ভ্যালিড প্রোডাক্ট পাওয়া যায়নি।');
            }

            do {
                $orderNumber = 'ARM-'.strtoupper(Str::random(3)).'-'.random_int(1000, 9999);
            } while (self::where('order_number', $orderNumber)->exists());

            $order = self::create([
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
            ]);

            $order->items()->createMany($items);

            return $order->load('items');
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
