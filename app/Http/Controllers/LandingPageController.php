<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Faq;
use App\Models\IncompleteOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Support\MetaTracker;
use App\Support\Seo;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();
        $products = Product::where('is_active', true)->with('category')->latest()->get();
        $settings = Setting::allWithDefaults();
        $settings = $this->applyHeroProduct($settings);
        $reviews = Review::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        $faqs = Faq::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        $productsFormatted = $products->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category ? $p->category->slug : 'other',
                'price' => (float) $p->price,
                'oldPrice' => $p->old_price ? (float) $p->old_price : null,
                'discount' => $p->discount_label,
                'url' => route('products.show', $p->slug),
                'image' => $p->image_url,
                'rating' => (float) $p->rating,
                'reviews' => (int) $p->reviews_count,
                'badge' => $p->badge,
                'shortDesc' => $p->short_description,
                'description' => $p->description,
                'specs' => $p->specs ?? [],
                'stock' => (int) $p->stock,
            ];
        });

        $seo = Seo::landing($settings, $products);

        return view('landing', compact('categories', 'products', 'productsFormatted', 'settings', 'reviews', 'faqs', 'seo'));
    }

    public function order(Request $request)
    {
        $request->merge(['customer_phone' => Order::normalizePhone((string) $request->input('customer_phone'))]);

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'customer_address' => 'required|string|max:1000',
            'delivery_area' => 'required|in:inside,outside',
            'order_note' => 'nullable|string|max:500',
            'visitor_token' => 'nullable|uuid',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1|max:50',
        ], [
            'customer_name.required' => 'আপনার নাম প্রদান করুন।',
            'customer_phone.required' => 'সঠিক মোবাইল নম্বর প্রদান করুন।',
            'customer_phone.regex' => '১১ ডিজিটের সঠিক মোবাইল নম্বর দিন (যেমন: 01805962220)।',
            'customer_address.required' => 'সম্পূর্ণ ঠিকানা প্রদান করুন।',
            'items.min' => 'অন্তত একটি প্রোডাক্ট নির্বাচন করুন।',
        ]);

        try {
            $order = DB::transaction(function () use ($validated, $request): Order {
                $order = Order::place($validated, $validated['items'], 'pending', MetaTracker::clientData($request) + ['visitor_token' => $validated['visitor_token'] ?? null]);

                IncompleteOrder::discardForOrder($validated['visitor_token'] ?? null, $validated['customer_phone']);

                return $order;
            });
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'অর্ডার প্রসেস করার সময় সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।',
            ], 500);
        }

        // Goes to Meta after this response has been sent; the browser sends the same event with the same id.
        app(MetaTracker::class)->purchase($order, MetaTracker::requestContext($request));

        $deliveryCharge = (float) $order->delivery_charge;
        $subtotal = (float) $order->subtotal;
        $grandTotal = (float) $order->grand_total;

        // Text shown on the order confirmation popup
        $deliveryText = ($order->delivery_area === 'inside') ? 'ঢাকা সিটির ভেতরে (৳'.$deliveryCharge.')' : 'ঢাকার বাইরে (৳'.$deliveryCharge.')';

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_address' => $order->customer_address,
            'delivery_area_text' => $deliveryText,
            'subtotal' => $subtotal,
            'delivery_charge' => $deliveryCharge,
            'grand_total' => $grandTotal,
            'items_summary' => $order->items->map(fn ($item) => "{$item->product_name} (×{$item->quantity})")->implode(', '),
            'event_id' => 'purchase-'.$order->id,
            'pixel_purchase_payload' => [
                'value' => $grandTotal,
                'currency' => 'BDT',
                'content_type' => 'product',
                'content_ids' => $order->items->pluck('product_id')->map(fn ($id): string => (string) $id)->all(),
                'contents' => $order->items->map(fn ($item): array => [
                    'id' => (string) $item->product_id,
                    'quantity' => $item->quantity,
                    'item_price' => (float) $item->unit_price,
                ])->all(),
                'num_items' => $order->items->sum('quantity'),
                'order_id' => $order->order_number,
            ],
        ]);
    }

    /**
     * When a hero product is chosen, its details replace the manual hero card text.
     *
     * @param  array<string, string>  $settings
     * @return array<string, string>
     */
    private function applyHeroProduct(array $settings): array
    {
        $product = filled($settings['hero_product_id'] ?? null)
            ? Product::where('is_active', true)->find($settings['hero_product_id'])
            : null;

        if (! $product) {
            return $settings;
        }

        $bangla = fn (float $amount): string => '৳'.strtr(number_format($amount), ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);

        return array_merge($settings, [
            'hero_image' => (string) $product->image,
            'hero_card_title' => $product->name,
            'hero_card_sub' => Str::limit((string) $product->short_description, 90),
            'hero_card_new_price' => $bangla((float) $product->price),
            'hero_card_old_price' => $product->old_price ? $bangla((float) $product->old_price) : '',
            'hero_badge' => $product->discount_label ? ($product->badge ? $product->badge.' • '.$product->discount_label : $product->discount_label) : (string) $product->badge,
        ]);
    }
}
