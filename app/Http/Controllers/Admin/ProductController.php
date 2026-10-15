<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /** Smallest accepted side in pixels; anything smaller is too tiny to show properly. */
    public const MIN_IMAGE_SIZE = 300;

    /** From this side up the popup zoom stays sharp; smaller photos are accepted with a warning. */
    public const GOOD_IMAGE_SIZE = 800;

    /** Upload limit in KB. Larger photos are scaled down on save, so this can be generous. */
    public const MAX_UPLOAD_KB = 10240;

    /** Longer side, in pixels, that saved photos are scaled down to. */
    public const STORED_IMAGE_MAX_SIDE = 1600;

    private const IMAGE_RULES = ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_UPLOAD_KB, 'dimensions:min_width='.self::MIN_IMAGE_SIZE.',min_height='.self::MIN_IMAGE_SIZE.',max_width=5000,max_height=5000'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $products = Product::with('category')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($category) => $category->where('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('admin.products.index', compact('products', 'search'));
    }

    public function create(): View
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request, imageRequired: true);

        $validated['slug'] = $this->uniqueSlug($validated['name']);
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['specs'] = $this->parseSpecs($validated['specs'] ?? null);
        $validated['image'] = $this->storeImage($request->file('image'), 'products', self::STORED_IMAGE_MAX_SIDE);

        Product::create($validated);

        return redirect()->route('admin.products.index')->with('success', 'নতুন প্রোডাক্ট সফলভাবে যুক্ত করা হয়েছে।');
    }

    public function edit(Product $product): View
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validated($request, imageRequired: false);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['specs'] = $this->parseSpecs($validated['specs'] ?? null);
        unset($validated['image']);

        if ($request->hasFile('image')) {
            $validated['image'] = $this->storeImage($request->file('image'), 'products', self::STORED_IMAGE_MAX_SIDE);
            Setting::deleteImage($product->image);
        }

        $product->update($validated);

        return redirect()->route('admin.products.index')->with('success', 'প্রোডাক্টের তথ্য সফলভাবে আপডেট করা হয়েছে।');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Setting::deleteImage($product->image);
        $product->delete();

        return back()->with('success', 'প্রোডাক্টটি সফলভাবে ডিলিট করা হয়েছে।');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $imageRequired): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|min:0|max:99999999',
            'old_price' => 'nullable|numeric|min:0|max:99999999|gte:price',
            'discount_text' => 'nullable|string|max:50',
            'image' => [$imageRequired ? 'required' : 'nullable', ...self::IMAGE_RULES],
            'badge' => 'nullable|string|max:50',
            'short_description' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:5000',
            'seo_title' => 'nullable|string|max:120',
            'seo_description' => 'nullable|string|max:320',
            'specs' => 'nullable|string|max:3000',
            'stock' => 'required|integer|min:0|max:1000000',
            'rating' => 'required|numeric|min:0|max:5',
            'reviews_count' => 'required|integer|min:0|max:1000000',
        ], [
            'old_price.gte' => 'পূর্বের মূল্য বর্তমান মূল্যের সমান বা বেশি হতে হবে।',
            'image.required' => 'প্রোডাক্টের ছবি আপলোড করুন।',
            'image.image' => 'ফাইলটি অবশ্যই একটি ছবি হতে হবে।',
            'image.mimes' => 'ছবি অবশ্যই JPG, PNG বা WEBP ফরম্যাটের হতে হবে।',
            'image.max' => 'ছবির সাইজ ১০ MB এর বেশি হতে পারবে না।',
            'image.dimensions' => 'ছবির সাইজ কমপক্ষে ৩০০×৩০০ এবং সর্বোচ্চ ৫০০০×৫০০০ পিক্সেল হতে হবে।',
            'image.uploaded' => 'ছবি আপলোড হয়নি। ফাইলের সাইজ সার্ভারের লিমিটের চেয়ে বড় হতে পারে।',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function parseSpecs(?string $specs): array
    {
        if (blank($specs)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode("\n", $specs))));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $counter = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
