<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\IncompleteOrder;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.admin');

        // $order->created_at->inShopTime()->format('h:i A') shows the stored UTC moment as Bangladesh time.
        Carbon::macro('inShopTime', function (): Carbon {
            /** @var Carbon $this */
            return $this->copy()->setTimezone(config('landing.timezone'));
        });
        View::composer(['admin.layout', 'auth.layout'], function ($view): void {
            $view->with('site', Setting::allWithDefaults());
        });

        View::composer('partials.site-footer', function ($view): void {
            $view->with([
                'footerCategories' => Category::where('is_active', true)->orderBy('sort_order')->get(['name', 'slug']),
                'footerProducts' => Product::where('is_active', true)->orderBy('id')->get(['id', 'name', 'slug']),
            ]);
        });

        View::composer('admin.layout', function ($view): void {
            $view->with('newIncompleteOrdersCount', IncompleteOrder::notExpired()->where('status', 'new')->count());
        });
    }
}
