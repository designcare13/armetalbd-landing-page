<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Support\Seo;
use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $base = Seo::baseUrl(Setting::allWithDefaults());
        $entries = Product::where('is_active', true)->orderBy('id')->get()->map(fn (Product $product): array => [
            'url' => $base.route('products.show', ['product' => $product->slug], false),
            'lastModified' => $this->lastModified($product),
            'image' => $product->image_url,
            'title' => $product->name,
        ]);

        // The XML declaration lives here, not in the Blade view: with PHP's short_open_tag enabled (common on shared
        // hosting) Blade would read "<?xml" as the start of PHP code and the page would fail.
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".view('seo.sitemap', [
            'homeUrl' => $base.'/',
            'homeLastModified' => $entries->max('lastModified') ?? now(),
            'entries' => $entries,
        ])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Products imported from an old database can have no date, or a date that is not a real one;
     * the sitemap must still be served, so fall back to the other date and then to now.
     */
    private function lastModified(Product $product): CarbonInterface
    {
        foreach (['updated_at', 'created_at'] as $column) {
            try {
                $date = $product->{$column};
            } catch (InvalidFormatException) {
                continue;
            }

            if ($date instanceof CarbonInterface && $date->year >= 2000) {
                return $date;
            }
        }

        return now();
    }
}
