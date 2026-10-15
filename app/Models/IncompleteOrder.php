<?php

namespace App\Models;

use Database\Factories\IncompleteOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A checkout form a visitor started (valid phone number typed) but has not submitted yet.
 * Kept apart from orders so stock, sales figures and the Meta Pixel are never affected.
 */
class IncompleteOrder extends Model
{
    /** @use HasFactory<IncompleteOrderFactory> */
    use HasFactory;

    public const RETENTION_DAYS = 30;

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'new' => 'নতুন',
        'no_answer' => 'ফোন ধরেনি',
        'call_later' => 'পরে নেবে',
        'ordered' => 'অর্ডার নিয়েছি',
        'not_interested' => 'আগ্রহী নন',
    ];

    protected $fillable = [
        'visitor_token',
        'customer_phone',
        'customer_name',
        'customer_address',
        'delivery_area',
        'items',
        'cart_total',
        'status',
        'last_captured_at',
    ];

    protected $casts = [
        'items' => 'array',
        'cart_total' => 'decimal:2',
        'last_captured_at' => 'datetime',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Rows still inside the retention window.
     */
    #[Scope]
    protected function notExpired(Builder $query): Builder
    {
        return $query->where('last_captured_at', '>=', now()->subDays(self::RETENTION_DAYS));
    }

    /**
     * Delete rows older than the retention period and return how many were removed.
     */
    public static function pruneExpired(): int
    {
        return static::where('last_captured_at', '<', now()->subDays(self::RETENTION_DAYS))->delete();
    }

    /**
     * Remove the visitor's rows once a real order exists, matching by browser token and by phone number
     * so an order placed from another device clears the follow-up entry too.
     */
    public static function discardForOrder(?string $visitorToken, string $phone): void
    {
        static::where('customer_phone', $phone)
            ->when($visitorToken, fn (Builder $query) => $query->orWhere('visitor_token', $visitorToken))
            ->delete();
    }
}
