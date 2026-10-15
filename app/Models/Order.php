<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
