<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class ProductPageController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $settings = Setting::allWithDefaults();
        $product->load('category');

        // Same-category products first, topped up with the newest others.
        $related = Product::where('is_active', true)
            ->whereKeyNot($product->id)
            ->orderByRaw('category_id = ? desc', [$product->category_id])
            ->latest()
            ->limit(4)
            ->get();

        $whatsappText = "আমি \"{$product->name}\" অর্ডার করতে চাই।";

        return view('products.show', [
            'product' => $product,
            'settings' => $settings,
            'seo' => Seo::product($settings, $product),
            'specRows' => $this->specRows($product->specs ?? []),
            'related' => $related,
            'orderUrl' => route('home', $product->stock > 0 ? ['buy' => $product->id] : []).'#checkoutSection',
            'whatsappUrl' => 'https://api.whatsapp.com/send?phone='.$settings['whatsapp'].'&text='.rawurlencode($whatsappText),
        ]);
    }

    /**
     * Turn "- ব্র্যান্ড: AR Metal" lines into label and value rows; lines without a colon stay as plain notes.
     *
     * @param  array<int, string>  $specs
     * @return array<int, array{label: string|null, value: string}>
     */
    private function specRows(array $specs): array
    {
        return collect($specs)
            ->map(fn ($line): string => trim((string) preg_replace('/^[\s\-–—•*]+/u', '', (string) $line)))
            ->filter()
            ->map(function (string $line): array {
                $colon = mb_strpos($line, ':');

                return $colon > 0
                    ? ['label' => trim(mb_substr($line, 0, $colon)), 'value' => trim(mb_substr($line, $colon + 1))]
                    : ['label' => null, 'value' => $line];
            })
            ->values()
            ->all();
    }
}
