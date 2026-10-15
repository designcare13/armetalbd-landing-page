<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The shop's own clock. Dates are stored in UTC, but a "day" for the shop owner is a day in Bangladesh,
 * so ranges like "today" or "from 5 Oct to 7 Oct" must start and end at Bangladesh midnight.
 */
final class ShopTime
{
    public static function timezone(): string
    {
        return (string) config('landing.timezone', 'Asia/Dhaka');
    }

    /** The start of a shop day, as a moment in the application's own time (UTC) ready for a query. */
    public static function startOfDay(CarbonInterface|string|null $date = null): Carbon
    {
        return self::inShop($date)->startOfDay()->setTimezone(config('app.timezone'));
    }

    /** The last second of a shop day, as a moment in the application's own time (UTC) ready for a query. */
    public static function endOfDay(CarbonInterface|string|null $date = null): Carbon
    {
        return self::inShop($date)->endOfDay()->setTimezone(config('app.timezone'));
    }

    private static function inShop(CarbonInterface|string|null $date): Carbon
    {
        return match (true) {
            $date === null => Carbon::now(self::timezone()),
            $date instanceof CarbonInterface => Carbon::instance($date)->setTimezone(self::timezone()),
            default => Carbon::createFromFormat('Y-m-d', $date, self::timezone())->setTimezone(self::timezone()),
        };
    }
}
