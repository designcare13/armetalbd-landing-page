<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds the search engine facing data of a page: canonical address, meta texts and schema.org JSON-LD.
 */
class Seo
{
    /**
     * The address search engines should treat as the site's main one. The admin can set it in the
     * settings; until then it is the address the visitor came in on.
     *
     * @param  array<string, string>  $settings
     */
    public static function baseUrl(array $settings): string
    {
        $configured = trim((string) ($settings['site_url'] ?? ''));

        return rtrim($configured !== '' ? $configured : request()->getSchemeAndHttpHost(), '/');
    }

    /**
     * Plain text for a meta description: no tags, one line, cut at a word boundary.
     */
    public static function describe(?string $text, int $limit = 155): string
    {
        $clean = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $text)));

        return Str::limit($clean, $limit, '…', preserveWords: true);
    }

    /**
     * Local numbers become international ones (01805962220 to +8801805962220), as search engines expect.
     */
    public static function phone(?string $phone): ?string
    {
        $digits = (string) preg_replace('/\D+/', '', (string) $phone);

        return match (true) {
            $digits === '' => null,
            (bool) preg_match('/^01\d{9}$/', $digits) => '+88'.$digits,
            default => '+'.$digits,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function encode(array $data): string
    {
        return json_encode(self::prune($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
    }

    /**
     * Page data for the landing page.
     *
     * @param  array<string, string>  $settings
     * @param  Collection<int, Product>  $products  active products
     * @return array<string, mixed>
     */
    public static function landing(array $settings, Collection $products): array
    {
        $base = self::baseUrl($settings);
        $title = $settings['site_title'] ?: $settings['brand_name'];

        return [
            'title' => $title,
            'description' => $settings['meta_description'],
            'canonical' => $base.'/',
            'image' => Setting::imageUrl($settings['meta_image']),
            'imageAlt' => $settings['brand_name'],
            'type' => 'website',
            'ogExtra' => [],
            'jsonLd' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    self::organization($settings, $base),
                    [
                        '@type' => 'WebSite',
                        '@id' => $base.'/#website',
                        'url' => $base.'/',
                        'name' => $settings['brand_name'],
                        'inLanguage' => 'bn-BD',
                        'publisher' => ['@id' => $base.'/#organization'],
                    ],
                    [
                        '@type' => 'WebPage',
                        '@id' => $base.'/#webpage',
                        'url' => $base.'/',
                        'name' => $title,
                        'description' => self::describe($settings['meta_description'], 300),
                        'inLanguage' => 'bn-BD',
                        'isPartOf' => ['@id' => $base.'/#website'],
                        'about' => ['@id' => $base.'/#organization'],
                    ],
                    [
                        '@type' => 'ItemList',
                        'name' => $settings['products_title'],
                        'itemListElement' => $products->values()->map(fn (Product $product, int $index): array => [
                            '@type' => 'ListItem',
                            'position' => $index + 1,
                            'name' => $product->name,
                            'url' => self::productUrl($base, $product),
                        ])->all(),
                    ],
                ],
            ],
        ];
    }

    /**
     * Page data for one product page.
     *
     * @param  array<string, string>  $settings
     * @return array<string, mixed>
     */
    public static function product(array $settings, Product $product): array
    {
        $base = self::baseUrl($settings);
        $url = self::productUrl($base, $product);
        $inStock = $product->stock > 0;
        $price = number_format((float) $product->price, 2, '.', '');
        $brand = $settings['brand_name'];

        $description = self::describe($product->short_description ?: $product->description, 155)
            ?: "{$product->name}, {$brand}-এর নিজস্ব কারখানায় তৈরি। সারা দেশে ক্যাশ অন ডেলিভারি।";

        return [
            'title' => "{$product->name} | ৳".number_format((float) $product->price)." | {$brand}",
            'description' => $description,
            'canonical' => $url,
            'image' => $product->image_url,
            'imageAlt' => $product->name,
            'type' => 'product',
            'ogExtra' => [
                'product:price:amount' => $price,
                'product:price:currency' => 'BDT',
                'product:availability' => $inStock ? 'instock' : 'out of stock',
            ],
            'jsonLd' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    self::organization($settings, $base),
                    [
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'হোম', 'item' => $base.'/'],
                            ['@type' => 'ListItem', 'position' => 2, 'name' => $product->name, 'item' => $url],
                        ],
                    ],
                    // No aggregateRating on purpose: the rating and review count are typed in by the admin,
                    // and search engines penalise review markup that is not backed by real reviews.
                    [
                        '@type' => 'Product',
                        '@id' => $url.'#product',
                        'name' => $product->name,
                        'image' => [$product->image_url],
                        'description' => self::describe($product->description ?: $product->short_description, 500),
                        'sku' => 'ARM-'.$product->id,
                        'category' => $product->category?->name,
                        'brand' => ['@type' => 'Brand', 'name' => $brand],
                        'url' => $url,
                        'offers' => [
                            '@type' => 'Offer',
                            'url' => $url,
                            'priceCurrency' => 'BDT',
                            'price' => $price,
                            'priceValidUntil' => now()->addYear()->toDateString(),
                            'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                            'itemCondition' => 'https://schema.org/NewCondition',
                            'seller' => ['@id' => $base.'/#organization'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private static function productUrl(string $base, Product $product): string
    {
        return $base.route('products.show', ['product' => $product->slug], false);
    }

    /**
     * The business as a local furniture shop, with contact details and social profiles.
     *
     * @param  array<string, string>  $settings
     * @return array<string, mixed>
     */
    private static function organization(array $settings, string $base): array
    {
        return [
            '@type' => 'FurnitureStore',
            '@id' => $base.'/#organization',
            'name' => $settings['brand_name'],
            'url' => $base.'/',
            'logo' => ['@type' => 'ImageObject', 'url' => Setting::imageUrl($settings['logo'])],
            'image' => Setting::imageUrl($settings['meta_image']),
            'description' => self::describe($settings['meta_description'], 300),
            'telephone' => self::phone($settings['phone']),
            'email' => $settings['email'],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings['address'],
                'addressLocality' => 'Dhaka',
                'addressCountry' => 'BD',
            ],
            'areaServed' => ['@type' => 'Country', 'name' => 'Bangladesh'],
            'sameAs' => array_values(array_filter([
                $settings['facebook_url'],
                $settings['instagram_url'],
                $settings['youtube_url'],
                $settings['tiktok_url'],
            ])),
        ];
    }

    /**
     * Drop empty values so the markup never carries blank fields.
     *
     * @param  array<int|string, mixed>  $data
     * @return array<int|string, mixed>
     */
    private static function prune(array $data): array
    {
        $kept = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::prune($value);
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $kept[$key] = $value;
        }

        return array_is_list($data) ? array_values($kept) : $kept;
    }
}
