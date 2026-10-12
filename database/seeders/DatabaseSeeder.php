<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default Admin User
        User::firstOrCreate(
            ['email' => 'admin@armetalbd.com'],
            [
                'name' => 'AR Metal Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Default System & SEO & Pixel Settings
        $settings = [
            'site_title' => 'AR Metal BD — প্রিমিয়াম সুইং চেয়ার (দোলনা) ও মেটাল ফার্নিচার',
            'meta_description' => 'সরাসরি নিজস্ব কারখানা থেকে প্রিমিয়াম কোয়ালিটির সুইং চেয়ার (দোলনা), বেবি কট, মেটাল বেড এবং কিচেন ট্রলি। সারাদেশে ক্যাশ অন ডেলিভারি!',
            'meta_keywords' => 'AR Metal BD, Swing Chair, Dolna, দোলনা, সুইং চেয়ার, Baby Cot, Metal Bed, Kitchen Trolley, Furniture Bangladesh',
            'meta_image' => 'https://armetalbd.com/uploads/1000077471_1772519549643_rj8kri.jpg',
            'facebook_pixel_id' => '', // Set the real Pixel ID from the admin settings page
            'phone' => '01805962220',
            'whatsapp' => '8801805962220',
            'email' => 'arif28391@gmail.com',
            'address' => 'মিরপুর, কালশী কবরস্থান, ঢাকা-১২১৬, বাংলাদেশ',
            'delivery_inside' => '150',
            'delivery_outside' => '300',
        ];

        foreach ($settings as $key => $val) {
            Setting::firstOrCreate(['key' => $key], ['value' => $val]);
        }

        // 3. Create Categories
        $catDolna = Category::firstOrCreate(['slug' => 'dolna'], [
            'name' => 'সুইং চেয়ার / দোলনা',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $catBabyCot = Category::firstOrCreate(['slug' => 'babycot'], [
            'name' => 'বেবি কট',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $catFurniture = Category::firstOrCreate(['slug' => 'furniture'], [
            'name' => 'বেড ও ফার্নিচার',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $catTrolley = Category::firstOrCreate(['slug' => 'trolley'], [
            'name' => 'কিচেন ট্রলি',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        // 4. Create Products
        $products = [
            [
                'category_id' => $catDolna->id,
                'name' => 'প্রিমিয়াম দোলনা সুইং চেয়ার (Swing Chair)',
                'slug' => 'swing-chair-premium',
                'price' => 13650,
                'old_price' => 16500,
                'discount_text' => '১৭% ছাড়',
                'image' => 'https://armetalbd.com/uploads/1000077471_1772519549643_rj8kri.jpg',
                'badge' => 'হট সেলিং',
                'rating' => 5.0,
                'reviews_count' => 48,
                'short_description' => 'আপনার ঘর বা বারান্দার সৌন্দর্য বাড়াতে প্রিমিয়াম মানের দোলনা সুইং চেয়ার। অত্যন্ত মজবুত ও আরামদায়ক।',
                'specs' => [
                    'উপাদান: এম.এস আইরন স্টিল ও চাইনিজ ফাইবার',
                    'ফিনিশিং: রোদে-বৃষ্টিতে নষ্ট না হওয়া হিট পেইন্ট',
                    'বাস্কেট সাইজ: হাইট ৪৪" ও ৪০"',
                    'স্ট্যান্ড হাইট: ৮২", নিচের রাউন্ড: ৩৮"',
                    'লোড ক্যাপাসিটি: ১৮০ কেজি পর্যন্ত',
                ],
                'is_featured' => true,
            ],
            [
                'category_id' => $catDolna->id,
                'name' => 'বার্ড নীর সুইং চেয়ার (Bird Neer Swing Chair)',
                'slug' => 'bird-neer-swing-chair',
                'price' => 13650,
                'old_price' => 16500,
                'discount_text' => '১৭% ছাড়',
                'image' => 'https://armetalbd.com/uploads/621824627_1336959648445963_6465691902683948368_n_1769424897358_9fn7zm.jpg',
                'badge' => 'জনপ্রিয়',
                'rating' => 4.9,
                'reviews_count' => 32,
                'short_description' => 'পাখির বাসার আদলে আকর্ষণীয় ডিজাইনের কমফোর্টেবল সুইং চেয়ার। কুশন সহ সম্পূর্ণ রেডি সেট।',
                'specs' => [
                    'ডিজাইন: এক্সক্লুসিভ বার্ড নীর প্যাটার্ন',
                    'উপাদান: হেভি ডিউটি মেটাল ফ্রেম',
                    'কালার: রাস্ট-প্রুফ কালো ও সোনালী ফিনিশ',
                    'লোড ক্যাপাসিটি: ১৮০ কেজি',
                    'ফ্রি নরম ওয়াশেবল কুশন সেট সহ',
                ],
                'is_featured' => true,
            ],
            [
                'category_id' => $catDolna->id,
                'name' => 'হেভি ডিউটি সুইং চেয়ার - ৩০০ কেজি ক্যাপাসিটি',
                'slug' => 'heavy-duty-swing-chair-300kg',
                'price' => 25000,
                'old_price' => 28500,
                'discount_text' => '১২% ছাড়',
                'image' => 'https://armetalbd.com/uploads/615120439_1331004042374857_6594332771594497050_n_1769579507614_n2lc3p.jpg',
                'badge' => 'প্রিমিয়াম',
                'rating' => 5.0,
                'reviews_count' => 19,
                'short_description' => '৩০০ কেজি লোড ক্যাপাসিটির এক্সট্রা লার্জ সুইং চেয়ার। পরিবার সহ বসার জন্য উপযুক্ত।',
                'specs' => [
                    'লোড ক্যাপাসিটি: ৩০০ কেজি টেস্টেড',
                    'পাইপ থিকনেস: ১৬ গেজ হেভি মেটাল',
                    'বাস্কেট: এক্সট্রা স্পেসিয়াস এরগোনমিক বাস্কেট',
                    'ফিনিশিং: ডাবল লেয়ার ইলেক্ট্রোস্ট্যাটিক পেইন্ট',
                ],
                'is_featured' => true,
            ],
            [
                'category_id' => $catFurniture->id,
                'name' => 'লাক্সারি মেটাল ক্যানোপি বেড (Canopy Bed Frame)',
                'slug' => 'luxury-metal-canopy-bed',
                'price' => 15500,
                'old_price' => 20000,
                'discount_text' => '২২% ছাড়',
                'image' => 'https://armetalbd.com/uploads/Untitled_design___2026_03_04T160445_264_1772618736573_0xksad.png',
                'badge' => 'আধুনিক ডিজাইন',
                'rating' => 4.8,
                'reviews_count' => 26,
                'short_description' => 'আধুনিক স্টিল ক্যানোপি বেড ফ্রেম। শব্দহীন, টেকসই এবং বেডরুমের রাজকীয় লুকের জন্য সেরা।',
                'specs' => [
                    'সাইজ: কুইন সাইজ (৫ ফুট x ৭ ফুট)',
                    'উপাদান: হাই-গ্রেড এম.এস স্টিল',
                    'কালার: ম্যাট ব্ল্যাক পাউডার কোটিং',
                    'নোইজ-ফ্রি ও নড়াচড়াহীন লক জয়েন্ট',
                ],
                'is_featured' => false,
            ],
            [
                'category_id' => $catBabyCot->id,
                'name' => 'প্রিমিয়াম বেবি কট (Baby Cot - মেহগনি ও মেটাল)',
                'slug' => 'premium-baby-cot',
                'price' => 12000,
                'old_price' => 14500,
                'discount_text' => '১৭% ছাড়',
                'image' => 'https://armetalbd.com/uploads/received_1762696934287430_1769502930855_newytx.jpeg',
                'badge' => 'নিরাপদ ও টেকসই',
                'rating' => 5.0,
                'reviews_count' => 41,
                'short_description' => 'শিশুর নিরাপদ ও আরামদায়ক ঘুমের জন্য বিশেষভাবে প্রস্তুতকৃত মজবুত বেবি কট।',
                'specs' => [
                    'সেফটি সাইড রেইলিং ও লক হুইল',
                    'উপাদান: স্মুথ ফিনিশ মেহগনি ও স্টিল ফ্রেম',
                    'হাইট অ্যাডজাস্টেবল বেস',
                    'সহজেই মুভ করার ৪টি চাকা',
                ],
                'is_featured' => true,
            ],
            [
                'category_id' => $catTrolley->id,
                'name' => 'মাল্টিফাংশনাল কিচেন ট্রলি (Kitchen Trolley)',
                'slug' => 'multifunctional-kitchen-trolley',
                'price' => 12000,
                'old_price' => 16000,
                'discount_text' => '২৫% ছাড়',
                'image' => 'https://armetalbd.com/preset/fixing.png',
                'badge' => 'স্মার্ট কিচেন',
                'rating' => 4.9,
                'reviews_count' => 35,
                'short_description' => 'আপনার কিচেনকে গোছানো ও আকর্ষণীয় করতে ২ পাল্লার ক্যাবিনেট, ড্রয়ার ও সাইড সেলফ ট্রলি।',
                'specs' => [
                    'মেলামাইন বোর্ড ও শক্ত মেটাল সাপোর্ট',
                    '৪টি মুভেবল চাকা (২টিতে লক সিস্টেম)',
                    '২ পাল্লার ক্যাবিনেট ও ১টি ড্রয়ার',
                    'সাইড মসলা ও বোতল রাখার অতিরিক্ত সেলফ',
                ],
                'is_featured' => true,
            ],
            [
                'category_id' => $catFurniture->id,
                'name' => 'এক্সিকিউটিভ উডেন ডিরেক্টর চেয়ার',
                'slug' => 'executive-wooden-director-chair',
                'price' => 14000,
                'old_price' => 16000,
                'discount_text' => '১২% ছাড়',
                'image' => 'https://armetalbd.com/uploads/Boss_Chair_09_01_1772619461181_8fk8q7.jpg',
                'badge' => 'এক্সিকিউটিভ',
                'rating' => 4.9,
                'reviews_count' => 18,
                'short_description' => 'সিনিয়র ম্যানেজমেন্ট পজিশনের জন্য প্রিমিয়াম লেদার ও উডেন ফিনিশ আরামদায়ক চেয়ার।',
                'specs' => [
                    'উচ্চমানের পিইউ লেদার ও ফোম কুশনিং',
                    'হাইড্রোলিক হাইট অ্যাডজাস্টমেন্ট',
                    '৩৬০ ডিগ্রি স্মুথ রিভলভিং',
                    'উডেন হ্যান্ডরেস্ট ও এরগোনমিক ব্যাকরেস্ট',
                ],
                'is_featured' => false,
            ],
            [
                'category_id' => $catFurniture->id,
                'name' => 'প্রিমিয়াম গর্জন বোর্ড ওয়ারড্রোব (Wardrobe)',
                'slug' => 'premium-gorjon-board-wardrobe',
                'price' => 22000,
                'old_price' => 28000,
                'discount_text' => '২১% ছাড়',
                'image' => 'https://armetalbd.com/uploads/Polish_20250603_120449269_1769525724193_nbkxet.jpg',
                'badge' => 'লং লাস্টিং',
                'rating' => 4.8,
                'reviews_count' => 14,
                'short_description' => 'উন্নত মানের গর্জন বোর্ডের তৈরি টেকসই ও দৃষ্টিনন্দন আধুনিক ৪ ড্রয়ার ওয়ারড্রোব।',
                'specs' => [
                    'উপাদান: গর্জন প্রসেসড বোর্ড',
                    'ঘুনে ও পোকায় না ধরা কেমিক্যাল ট্রিটমেন্ট',
                    'স্মুথ টেলিস্কোপিক ড্রয়ার চ্যানেল',
                    'ডাবল সিকিউরিটি লক সিস্টেম',
                ],
                'is_featured' => false,
            ],
            [
                'category_id' => $catFurniture->id,
                'name' => 'হেভি সিঙ্গেল মেটাল বেড (Single Metal Bed)',
                'slug' => 'heavy-single-metal-bed',
                'price' => 11500,
                'old_price' => 12500,
                'discount_text' => '৮% ছাড়',
                'image' => 'https://armetalbd.com/uploads/97966_large_89e638fb_844e_4a32_882e_bb0dfb628dfa_1772278174361_kcqcq1.png',
                'badge' => 'মজবুত',
                'rating' => 4.7,
                'reviews_count' => 22,
                'short_description' => 'ব্যাচেলর, হোস্টেল বা গেস্ট রুমের জন্য হাই-কোয়ালিটি মেটালের মজবুত ও লং-লাস্টিং বেড।',
                'specs' => [
                    'সাইজ: ৩.৫ ফুট x ৭ ফুট',
                    'মেটেরিয়াল: নন-রাস্টিং মাইল্ড স্টিল',
                    'কালার: ব্ল্যাক হিট পেইন্ট',
                    'ইজি অ্যাসেম্বল ও ডিসঅ্যাসেম্বল',
                ],
                'is_featured' => false,
            ],
        ];

        foreach ($products as $p) {
            Product::firstOrCreate(['slug' => $p['slug']], $p);
        }
        // 5. Customer reviews
        $reviews = [
            ['name' => 'তানজিলা আহমেদ', 'location' => 'উত্তরা, ঢাকা', 'body' => 'আমি একটি বার্ড নীর সুইং চেয়ার অর্ডার করেছিলাম। সত্যি বলতে ছবির চেয়ে বাস্তবে আরও সুন্দর ও মজবুত। কালার ফিনিশিং দুর্দান্ত এবং কুশনটা খুব নরম। ডেলিভারিও খুব দ্রুত পেয়েছি।', 'sort_order' => 1],
            ['name' => 'কামরুল হাসান', 'location' => 'চট্টগ্রাম সদর', 'body' => 'প্রথমে অনলাইনে মেটাল ফার্নিচার অর্ডার করতে একটু ইতস্তত করছিলাম। কিন্তু ক্যাশ অন ডেলিভারিতে মাল পেয়ে চেক করে টাকা দিতে পেরে নিশ্চিন্ত হলাম। কোয়ালিটি ১০০ তে ১০০!', 'sort_order' => 2],
            ['name' => 'শারমিন জাহান', 'location' => 'রাজশাহী', 'body' => 'বেবি কট আর কিচেন ট্রলি দুটিই একসাথে নিয়েছি। প্যাকেজিং খুবই নিখুঁত ছিল। ট্রলির চাকাগুলো খুব স্মুথ চলে এবং বেবি কটের কাঠের ফিনিশিং একদম মসৃণ। ধন্যবাদ এআর মেটাল!', 'sort_order' => 3],
        ];

        foreach ($reviews as $review) {
            Review::firstOrCreate(['name' => $review['name'], 'body' => $review['body']], $review + ['rating' => 5, 'is_active' => true]);
        }

        // 6. FAQs
        $faqs = [
            ['question' => 'ডেলিভারি পেতে কতদিন সময় লাগবে?', 'answer' => 'ঢাকা সিটির ভেতরে সাধারণত ২৪ থেকে ৪৮ ঘণ্টার মধ্যে এবং ঢাকার বাইরে সমগ্র বাংলাদেশে ২ থেকে ৪ দিনের মধ্যে নিরাপদ হোম ডেলিভারি পৌঁছে দেওয়া হয়।', 'sort_order' => 1],
            ['question' => 'ডেলিভারির পূর্বে কোনো অগ্রিম টাকা দিতে হবে?', 'answer' => 'না, সম্পূর্ণ ক্যাশ অন ডেলিভারি সুবিধা রয়েছে। পণ্য হাতে পেয়ে সম্পূর্ণ দেখে ও চেক করে তারপর ডেলিভারিম্যানের কাছে টাকা পরিশোধ করতে পারবেন।', 'sort_order' => 2],
            ['question' => 'দোলনা বা সুইং চেয়ারের ফিটিং কীভাবে করব?', 'answer' => 'আমাদের সুইং চেয়ারের প্রতিটি পার্টস খুব সহজে অ্যাসেম্বল করা যায়। সাথে প্রয়োজনীয় নাট-বোল্ট ও স্প্রিং হুক দেওয়া থাকে। যেকোনো ব্যক্তি মাত্র ৫ মিনিটে কোনো বাড়তি মিস্ত্রি ছাড়াই এটি সেটআপ করতে পারেন।', 'sort_order' => 3],
            ['question' => 'প্রোডাক্টে কোনো সমস্যা থাকলে রিটার্ন বা রিপ্লেসমেন্ট সুবিধা আছে কি?', 'answer' => 'অবশ্যই! ডেলিভারির সময় কোনো প্রকার ত্রুটি বা অমিল পরিলক্ষিত হলে আপনি তাৎক্ষণিক ডেলিভারিম্যানকে পণ্য ফেরত দিতে পারবেন অথবা আমাদের হেল্পলাইনে জানালে আমরা তাৎক্ষণিক রিপ্লেসমেন্ট ব্যবস্থা করব।', 'sort_order' => 4],
        ];

        foreach ($faqs as $faq) {
            Faq::firstOrCreate(['question' => $faq['question']], $faq + ['is_active' => true]);
        }
    }
}
