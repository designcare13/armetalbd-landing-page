<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Support\Seo;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $base = Seo::baseUrl(Setting::allWithDefaults());
        $products = Product::where('is_active', true)->orderBy('id')->get();

        $xml = view('seo.sitemap', [
            'homeUrl' => $base.'/',
            'homeLastModified' => $products->max('updated_at') ?? now(),
            'entries' => $products->map(fn (Product $product): array => [
                'url' => $base.route('products.show', ['product' => $product->slug], false),
                'lastModified' => $product->updated_at,
                'image' => $product->image_url,
                'title' => $product->name,
            ]),
        ])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
