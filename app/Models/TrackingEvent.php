<?php

namespace App\Models;

use Database\Factories\TrackingEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One event sent to Meta from the server. Holds only the outcome, never customer data.
 */
class TrackingEvent extends Model
{
    /** @use HasFactory<TrackingEventFactory> */
    use HasFactory;

    public const RETENTION_DAYS = 60;

    protected $fillable = [
        'event_name',
        'event_id',
        'reference',
        'value',
        'status',
        'events_received',
        'fbtrace_id',
        'error',
        'is_test',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'events_received' => 'integer',
        'is_test' => 'boolean',
    ];

    /**
     * Delete old history so the table stays small.
     */
    public static function pruneOld(): int
    {
        return static::where('created_at', '<', now()->subDays(self::RETENTION_DAYS))->delete();
    }
}
