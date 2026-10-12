<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Faq;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
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
                'discount' => $p->discount_text,
                'image' => $p->image_url,
                'rating' => (float) $p->rating,
                'reviews' => (int) $p->reviews_count,
                'badge' => $p->badge,
                'shortDesc' => $p->short_description,
                'specs' => $p->specs ?? [],
                'stock' => (int) $p->stock,
            ];
        });

        return view('landing', compact('categories', 'products', 'productsFormatted', 'settings', 'reviews', 'faqs'));
    }

    public function order(Request $request)
    {
        $request->merge(['customer_phone' => $this->normalizePhone((string) $request->input('customer_phone'))]);

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'customer_address' => 'required|string|max:1000',
            'delivery_area' => 'required|in:inside,outside',
            'order_note' => 'nullable|string|max:500',
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

        $siteSettings = Setting::allWithDefaults();
        $insideCharge = (float) $siteSettings['delivery_inside'];
        $outsideCharge = (float) $siteSettings['delivery_outside'];
        $deliveryCharge = ($validated['delivery_area'] === 'inside') ? $insideCharge : $outsideCharge;

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $orderItemsData = [];

            foreach ($validated['items'] as $itemInput) {
                $product = Product::where('is_active', true)->lockForUpdate()->find($itemInput['id']);
                if (! $product) {
                    continue;
                }

                $qty = (int) $itemInput['qty'];

                if ($product->stock < $qty) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => $product->stock > 0
                            ? "দুঃখিত, '{$product->name}' এর মাত্র {$product->stock} টি স্টকে আছে।"
                            : "দুঃখিত, '{$product->name}' এখন স্টকে নেই।",
                    ], 422);
                }

                $product->decrement('stock', $qty);
                $price = (float) $product->price;
                $lineTotal = $price * $qty;
                $subtotal += $lineTotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image' => $product->image_url,
                    'unit_price' => $price,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                ];
            }

            if (empty($orderItemsData)) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'নির্বাচিত কোনো ভ্যালিড প্রোডাক্ট পাওয়া যায়নি।',
                ], 422);
            }

            do {
                $orderNumber = 'ARM-'.strtoupper(Str::random(3)).'-'.random_int(1000, 9999);
            } while (Order::where('order_number', $orderNumber)->exists());
            $grandTotal = $subtotal + $deliveryCharge;

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'delivery_area' => $validated['delivery_area'],
                'delivery_charge' => $deliveryCharge,
                'subtotal' => $subtotal,
                'grand_total' => $grandTotal,
                'payment_method' => 'cod',
                'order_note' => $validated['order_note'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($orderItemsData as $item) {
                $order->items()->create($item);
            }

            DB::commit();

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
                'items_summary' => collect($orderItemsData)->map(fn ($i) => "{$i['product_name']} (×{$i['quantity']})")->implode(', '),
                'pixel_purchase_payload' => [
                    'value' => $grandTotal,
                    'currency' => 'BDT',
                    'content_ids' => collect($orderItemsData)->pluck('product_id')->toArray(),
                    'num_items' => collect($orderItemsData)->sum('quantity'),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'অর্ডার প্রসেস করার সময় সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।',
            ], 500);
        }
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
            'hero_badge' => $product->discount_text ? ($product->badge ? $product->badge.' • '.$product->discount_text : $product->discount_text) : (string) $product->badge,
        ]);
    }

    /**
     * Accept Bangla digits, spaces, dashes and +88 / 88 prefixes in phone numbers.
     */
    private function normalizePhone(string $phone): string
    {
        $phone = strtr($phone, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']);
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            return '0'.substr($digits, 3);
        }

        return $digits;
    }
}
