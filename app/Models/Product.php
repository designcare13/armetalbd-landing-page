<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /** At or below this many pieces the admin list marks the stock as running low. */
    public const LOW_STOCK_LIMIT = 5;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'price',
        'old_price',
        'discount_text',
        'image',
        'badge',
        'short_description',
        'description',
        'seo_title',
        'seo_description',
        'specs',
        'rating',
        'reviews_count',
        'stock',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'specs' => 'array',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'rating' => 'decimal:1',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getImageUrlAttribute(): string
    {
        return Setting::imageUrl($this->image, 'https://armetalbd.com/preset/fixing1.png');
    }

    /**
     * Discount percentage derived from the old and current price, rounded down
     * so the storefront never overstates the discount. Null when there is no real discount.
     */
    public function getDiscountPercentAttribute(): ?int
    {
        $oldCents = (int) round((float) $this->old_price * 100);
        $priceCents = (int) round((float) $this->price * 100);

        if ($oldCents <= 0 || $priceCents >= $oldCents) {
            return null;
        }

        $percent = intdiv(($oldCents - $priceCents) * 100, $oldCents);

        return $percent >= 1 ? $percent : null;
    }

    /**
     * Automatic label built from the prices only, e.g. "১৩% ছাড়".
     */
    public function getAutomaticDiscountLabelAttribute(): ?string
    {
        if ($this->discount_percent === null) {
            return null;
        }

        return strtr($this->discount_percent.'% ছাড়', [
            '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
            '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
        ]);
    }

    /**
     * Text shown on the storefront: the admin's custom text if set,
     * otherwise the automatic percentage label.
     */
    public function getDiscountLabelAttribute(): ?string
    {
        return filled($this->discount_text) ? $this->discount_text : $this->automatic_discount_label;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
