<?php

use App\Support\Video;

/*
|--------------------------------------------------------------------------
| Landing page content schema
|--------------------------------------------------------------------------
|
| Every editable piece of the public landing page is declared here once:
| its label, input type, default text and validation rules. The admin
| settings page, validation and the landing page defaults are all built
| from this file. Types: text, textarea, list (one item per line), image.
|
*/

$imageRules = 'mimes:jpg,jpeg,png,webp|max:2048';

return [

    'image_disk' => 'uploads',

    'tabs' => [
        'general' => ['label' => 'ব্র্যান্ড ও যোগাযোগ', 'icon' => 'fa-solid fa-id-card', 'groups' => ['brand', 'contact', 'delivery']],
        'hero' => ['label' => 'হিরো ও টপ বার', 'icon' => 'fa-solid fa-star', 'groups' => ['topbar', 'hero']],
        'sections' => ['label' => 'ট্রাস্ট ও প্রোডাক্ট সেকশন', 'icon' => 'fa-solid fa-layer-group', 'groups' => ['trust', 'products_section', 'spotlight', 'video_section', 'checkout_section']],
        'footer' => ['label' => 'রিভিউ, FAQ ও ফুটার', 'icon' => 'fa-solid fa-comments', 'groups' => ['reviews_section', 'faq_section', 'footer']],
        'seo' => ['label' => 'SEO ও পিক্সেল', 'icon' => 'fa-solid fa-magnifying-glass-chart', 'groups' => ['seo']],
    ],

    'groups' => [

        'brand' => [
            'title' => 'ব্র্যান্ড, লোগো ও আইকন',
            'icon' => 'fa-solid fa-image',
            'fields' => [
                'brand_name' => ['label' => 'ব্র্যান্ডের নাম', 'type' => 'text', 'default' => 'AR METAL', 'rules' => 'nullable|string|max:60'],
                'brand_tagline' => ['label' => 'ব্র্যান্ড ট্যাগলাইন', 'type' => 'text', 'default' => 'দীর্ঘস্থায়ী শক্তির প্রতিশ্রুতি', 'rules' => 'nullable|string|max:120'],
                'logo' => [
                    'label' => 'ওয়েবসাইট লোগো', 'type' => 'image',
                    'default' => 'https://armetalbd.com/uploads/3_1769424079361_u87afs.png',
                    'rules' => $imageRules.'|dimensions:min_width=40,min_height=40,max_width=2000,max_height=2000',
                    'hint' => 'JPG, PNG বা WEBP • সর্বোচ্চ ২ MB • কমপক্ষে ৪০×৪০ পিক্সেল',
                ],
                'favicon' => [
                    'label' => 'ফেভিকন (ব্রাউজার ট্যাব আইকন)', 'type' => 'image',
                    'default' => 'https://armetalbd.com/uploads/2_1769423679416_zuru23.png',
                    'rules' => 'mimes:jpg,jpeg,png,webp,ico|max:512',
                    'hint' => 'PNG, ICO, JPG বা WEBP • সর্বোচ্চ ৫১২ KB • বর্গাকার ছবি ভালো',
                ],
            ],
        ],

        'contact' => [
            'title' => 'যোগাযোগ ও সোশ্যাল লিংক',
            'icon' => 'fa-solid fa-address-book',
            'fields' => [
                'phone' => ['label' => 'হেল্পলাইন মোবাইল নম্বর', 'type' => 'text', 'default' => '01805962220', 'rules' => ['nullable', 'regex:/^\+?[0-9]{8,15}$/'], 'hint' => 'শুধু সংখ্যা, যেমন: 01805962220'],
                'whatsapp' => ['label' => 'হোয়াটসঅ্যাপ নম্বর (কান্ট্রি কোড সহ)', 'type' => 'text', 'default' => '8801805962220', 'rules' => ['nullable', 'regex:/^[0-9]{10,15}$/'], 'hint' => '+ ছাড়া, যেমন: 8801805962220'],
                'email' => ['label' => 'অফিসিয়াল ইমেইল', 'type' => 'text', 'default' => 'arif28391@gmail.com', 'rules' => 'nullable|email|max:255'],
                'address' => ['label' => 'শোরুম / কারখানার ঠিকানা', 'type' => 'text', 'default' => 'মিরপুর, কালশী কবরস্থান, ঢাকা-১২১৬, বাংলাদেশ', 'rules' => 'nullable|string|max:500'],
                'facebook_url' => ['label' => 'ফেসবুক পেজ লিংক', 'type' => 'text', 'default' => 'https://www.facebook.com/armetalshop', 'rules' => 'nullable|url:http,https|max:500'],
                'instagram_url' => ['label' => 'ইনস্টাগ্রাম লিংক', 'type' => 'text', 'default' => '', 'rules' => 'nullable|url:http,https|max:500'],
                'youtube_url' => ['label' => 'ইউটিউব লিংক', 'type' => 'text', 'default' => '', 'rules' => 'nullable|url:http,https|max:500'],
                'tiktok_url' => ['label' => 'টিকটক লিংক', 'type' => 'text', 'default' => '', 'rules' => 'nullable|url:http,https|max:500'],
            ],
        ],

        'delivery' => [
            'title' => 'ডেলিভারি চার্জ',
            'icon' => 'fa-solid fa-truck-fast',
            'fields' => [
                'delivery_inside' => ['label' => 'ঢাকা সিটির ভেতরে ডেলিভারি চার্জ (৳)', 'type' => 'text', 'default' => '150', 'rules' => 'nullable|numeric|min:0|max:100000'],
                'delivery_outside' => ['label' => 'ঢাকার বাইরে ডেলিভারি চার্জ (৳)', 'type' => 'text', 'default' => '300', 'rules' => 'nullable|numeric|min:0|max:100000'],
            ],
        ],

        'topbar' => [
            'title' => 'টপ বার',
            'icon' => 'fa-solid fa-bullhorn',
            'fields' => [
                'topbar_badge' => ['label' => 'টপ বারের ব্যাজ টেক্সট', 'type' => 'text', 'default' => 'সারাদেশে ক্যাশ অন ডেলিভারি', 'rules' => 'nullable|string|max:120'],
            ],
        ],

        'hero' => [
            'title' => 'হিরো সেকশন (সবার উপরের অংশ)',
            'icon' => 'fa-solid fa-star',
            'fields' => [
                'hero_product_id' => ['label' => 'হিরো কার্ডে দেখানোর প্রোডাক্ট', 'type' => 'product', 'default' => '', 'rules' => 'nullable|integer|exists:products,id', 'hint' => 'প্রোডাক্ট বেছে নিলে ছবি, নাম, বিবরণ ও দাম সেই প্রোডাক্ট থেকে অটোমেটিক আসবে। "ম্যানুয়াল" রাখলে নিচের ঘরগুলো ব্যবহার হবে।'],
                'hero_pill' => ['label' => 'ছোট ব্যাজ টেক্সট', 'type' => 'text', 'default' => '১০০% নিজস্ব কারখানায় প্রস্তুত প্রিমিয়াম মেটাল ফার্নিচার', 'rules' => 'nullable|string|max:150'],
                'hero_title' => ['label' => 'শিরোনাম (শুরুর অংশ)', 'type' => 'text', 'default' => 'আপনার ঘরের আভিজাত্য বাড়াতে', 'rules' => 'nullable|string|max:200'],
                'hero_highlight' => ['label' => 'শিরোনামের হাইলাইট (সবুজ রঙে দেখাবে)', 'type' => 'text', 'default' => 'প্রিমিয়াম সুইং চেয়ার (দোলনা)', 'rules' => 'nullable|string|max:150'],
                'hero_title_after' => ['label' => 'শিরোনাম (শেষ অংশ)', 'type' => 'text', 'default' => 'ও মেটাল ফার্নিচার', 'rules' => 'nullable|string|max:200'],
                'hero_desc' => ['label' => 'বিবরণ', 'type' => 'textarea', 'default' => 'উচ্চমানের এম.এস আইরন স্টিল ও চাইনিজ ফাইবার দিয়ে নিখুঁত ফিনিশিংয়ে তৈরি। রোদে-বৃষ্টিতে কোনো রং নষ্ট হবে না, ১৮০ থেকে ৩০০ কেজি পর্যন্ত নিশ্চিত ভারবহন ক্ষমতা!', 'rules' => 'nullable|string|max:600'],
                'hero_points' => ['label' => 'টিক চিহ্নিত পয়েন্ট (প্রতি লাইনে একটি)', 'type' => 'list', 'default' => "মরিচারোধক হিট পেইন্ট ফিনিশিং\n১৮০-৩০০ কেজি লোড টেস্টেড\nক্যাশ অন ডেলিভারি সুবিধা\nসারা দেশে দ্রুত হোম ডেলিভারি", 'rules' => 'nullable|string|max:800'],
                'hero_image' => [
                    'label' => 'হিরো ছবি', 'type' => 'image',
                    'default' => 'https://armetalbd.com/uploads/1000077471_1772519549643_rj8kri.jpg',
                    'rules' => $imageRules.'|dimensions:min_width=300,min_height=300,max_width=5000,max_height=5000',
                    'hint' => 'JPG, PNG বা WEBP • সর্বোচ্চ ২ MB • কমপক্ষে ৩০০×৩০০ পিক্সেল',
                ],
                'hero_badge' => ['label' => 'ছবির উপরের ব্যাজ', 'type' => 'text', 'default' => 'সর্বোচ্চ বিক্রিত আইটেম • ১৭% ছাড়', 'rules' => 'nullable|string|max:120'],
                'hero_card_title' => ['label' => 'ছবির নিচের প্রোডাক্টের নাম', 'type' => 'text', 'default' => 'প্রিমিয়াম দোলনা সুইং চেয়ার', 'rules' => 'nullable|string|max:150'],
                'hero_card_sub' => ['label' => 'ছবির নিচের ছোট বিবরণ', 'type' => 'text', 'default' => 'বাস্কেট সাইজ: ৪৪" × ৪০" | লোড: ১৮০ কেজি', 'rules' => 'nullable|string|max:200'],
                'hero_card_old_price' => ['label' => 'পূর্বের মূল্য (টেক্সট)', 'type' => 'text', 'default' => '৳১৬,৫০০', 'rules' => 'nullable|string|max:30'],
                'hero_card_new_price' => ['label' => 'বর্তমান মূল্য (টেক্সট)', 'type' => 'text', 'default' => '৳১৩,৬৫০', 'rules' => 'nullable|string|max:30'],
                'hero_stat_num' => ['label' => 'ভাসমান স্ট্যাটের সংখ্যা', 'type' => 'text', 'default' => '৪,৫০০+', 'rules' => 'nullable|string|max:30'],
                'hero_stat_text' => ['label' => 'ভাসমান স্ট্যাটের লেখা', 'type' => 'text', 'default' => 'সন্তুষ্ট গ্রাহক সারাদেশে', 'rules' => 'nullable|string|max:80'],
            ],
        ],

        'trust' => [
            'title' => 'ট্রাস্ট ব্যাজ (৪টি বক্স)',
            'icon' => 'fa-solid fa-shield-halved',
            'fields' => [
                'trust_1_title' => ['label' => 'বক্স ১ - শিরোনাম', 'type' => 'text', 'default' => 'ক্যাশ অন ডেলিভারি', 'rules' => 'nullable|string|max:80'],
                'trust_1_desc' => ['label' => 'বক্স ১ - বিবরণ', 'type' => 'text', 'default' => 'পণ্য হাতে পেয়ে চেক করে মূল্য দিন', 'rules' => 'nullable|string|max:150'],
                'trust_2_title' => ['label' => 'বক্স ২ - শিরোনাম', 'type' => 'text', 'default' => 'নিজস্ব ফ্যাক্টরিতে তৈরি', 'rules' => 'nullable|string|max:80'],
                'trust_2_desc' => ['label' => 'বক্স ২ - বিবরণ', 'type' => 'text', 'default' => 'মাঝখানে কোনো মধ্যস্বত্বভোগী নেই', 'rules' => 'nullable|string|max:150'],
                'trust_3_title' => ['label' => 'বক্স ৩ - শিরোনাম', 'type' => 'text', 'default' => 'হেভি ডিউটি ও মরিচারোধক', 'rules' => 'nullable|string|max:80'],
                'trust_3_desc' => ['label' => 'বক্স ৩ - বিবরণ', 'type' => 'text', 'default' => 'দীর্ঘস্থায়ী হিট পেইন্ট ও প্রিমিয়াম স্টিল', 'rules' => 'nullable|string|max:150'],
                'trust_4_title' => ['label' => 'বক্স ৪ - শিরোনাম', 'type' => 'text', 'default' => 'সারা দেশে ডেলিভারি', 'rules' => 'nullable|string|max:80'],
                'trust_4_desc' => ['label' => 'বক্স ৪ - বিবরণ', 'type' => 'text', 'default' => 'নিরাপদ প্যাকেজিংয়ে দ্রুততম সময়ে পৌঁছায়', 'rules' => 'nullable|string|max:150'],
            ],
        ],

        'products_section' => [
            'title' => 'প্রোডাক্ট সেকশনের হেডিং',
            'icon' => 'fa-solid fa-layer-group',
            'fields' => [
                'products_pill' => ['label' => 'ছোট ব্যাজ', 'type' => 'text', 'default' => 'আমাদের প্রোডাক্ট কালেকশন', 'rules' => 'nullable|string|max:80'],
                'products_title' => ['label' => 'শিরোনাম', 'type' => 'text', 'default' => 'পছন্দের প্রোডাক্টটি সিলেক্ট করে সরাসরি অর্ডার করুন', 'rules' => 'nullable|string|max:200'],
                'products_desc' => ['label' => 'বিবরণ', 'type' => 'textarea', 'default' => 'আপনি চাইলে একাধিক প্রোডাক্ট একসাথে কার্টে যোগ করে একসাথেই অর্ডার করতে পারেন। প্রতিটি প্রোডাক্ট অরিজিনাল ফ্যাক্টরি মেটেরিয়ালে নির্মিত।', 'rules' => 'nullable|string|max:400'],
            ],
        ],

        'spotlight' => [
            'title' => 'স্পটলাইট সেকশন (কেন আমাদের প্রোডাক্ট সেরা)',
            'icon' => 'fa-solid fa-award',
            'fields' => [
                'spotlight_pill' => ['label' => 'ছোট ব্যাজ', 'type' => 'text', 'default' => 'স্পেশাল ফিচার ও গুণগত মান', 'rules' => 'nullable|string|max:80'],
                'spotlight_title' => ['label' => 'শিরোনাম', 'type' => 'text', 'default' => 'কেন এআর মেটালের দোলনা সুইং চেয়ার সবার চেয়ে আলাদা?', 'rules' => 'nullable|string|max:200'],
                'spotlight_desc' => ['label' => 'বিবরণ', 'type' => 'textarea', 'default' => 'বাজারে সাধারণ পাতলা পাইপের দোলনা কিছুদিন পর বেঁকে যায় বা মরিচা ধরে রঙ নষ্ট হয়ে যায়। এআর মেটালের প্রতিটি সুইং চেয়ার আমরা হেভি-গেজ পাইপ ও চাইনিজ ফাইবার দিয়ে এমনভাবে তৈরি করি যাতে বছরের পর বছর কোনো সমস্যা না হয়।', 'rules' => 'nullable|string|max:600'],
                'spotlight_image' => [
                    'label' => 'স্পটলাইট ছবি', 'type' => 'image',
                    'default' => 'https://armetalbd.com/uploads/615120439_1331004042374857_6594332771594497050_n_1769579507614_n2lc3p.jpg',
                    'rules' => $imageRules.'|dimensions:min_width=300,min_height=300,max_width=5000,max_height=5000',
                    'hint' => 'JPG, PNG বা WEBP • সর্বোচ্চ ২ MB • কমপক্ষে ৩০০×৩০০ পিক্সেল',
                ],
                'spotlight_1_title' => ['label' => 'ফিচার ১ - শিরোনাম', 'type' => 'text', 'default' => '১৮০ - ৩০০ কেজি লোড', 'rules' => 'nullable|string|max:80'],
                'spotlight_1_desc' => ['label' => 'ফিচার ১ - বিবরণ', 'type' => 'text', 'default' => 'ভারী ওজনের মানুষ অনায়াসে নিরাপদে বসতে পারবেন, ভাঙার কোনো ভয় নেই।', 'rules' => 'nullable|string|max:200'],
                'spotlight_2_title' => ['label' => 'ফিচার ২ - শিরোনাম', 'type' => 'text', 'default' => 'ইলেক্ট্রোস্ট্যাটিক হিট পেইন্ট', 'rules' => 'nullable|string|max:80'],
                'spotlight_2_desc' => ['label' => 'ফিচার ২ - বিবরণ', 'type' => 'text', 'default' => 'বৃষ্টির পানি বা রোদে রঙ কখনো নষ্ট হবে না, সবসময় নতুনের মতো চকচক করবে।', 'rules' => 'nullable|string|max:200'],
                'spotlight_3_title' => ['label' => 'ফিচার ৩ - শিরোনাম', 'type' => 'text', 'default' => 'ফ্রি ওয়াশেবল নরম কুশন', 'rules' => 'nullable|string|max:80'],
                'spotlight_3_desc' => ['label' => 'ফিচার ৩ - বিবরণ', 'type' => 'text', 'default' => 'বসার আরামদায়ক অনুভূতির জন্য সাথে পাচ্ছেন উন্নত মানের সফট কুশন সেট।', 'rules' => 'nullable|string|max:200'],
                'spotlight_4_title' => ['label' => 'ফিচার ৪ - শিরোনাম', 'type' => 'text', 'default' => 'সহজ ইনস্টলেশন', 'rules' => 'nullable|string|max:80'],
                'spotlight_4_desc' => ['label' => 'ফিচার ৪ - বিবরণ', 'type' => 'text', 'default' => 'যেকোনো সাধারণ মানুষ মাত্র ৫ মিনিটে নিজেই ফিটিং করতে পারবেন।', 'rules' => 'nullable|string|max:200'],
            ],
        ],

        'video_section' => [
            'title' => 'ভিডিও সেকশন (অর্ডার ফর্মের ঠিক উপরে)',
            'icon' => 'fa-solid fa-circle-play',
            'fields' => [
                'video_url' => ['label' => 'ইউটিউব ভিডিওর লিংক', 'type' => 'text', 'default' => 'https://www.youtube.com/watch?v=BqT6m1pDlmo', 'rules' => ['nullable', 'url:http,https', 'max:300', 'regex:'.Video::YOUTUBE_PATTERN], 'hint' => 'ইউটিউব থেকে ভিডিওর লিংক কপি করে এখানে পেস্ট করুন (youtube.com বা youtu.be)। খালি রাখলে এই সেকশন ওয়েবসাইটে দেখানো হবে না।'],
                'video_pill' => ['label' => 'ছোট ব্যাজ', 'type' => 'text', 'default' => 'প্রোডাক্ট ডেমো ভিডিও', 'rules' => 'nullable|string|max:80'],
                'video_title' => ['label' => 'শিরোনাম', 'type' => 'text', 'default' => 'ভিডিওতে দেখুন প্রোডাক্টের আসল মান', 'rules' => 'nullable|string|max:200'],
                'video_desc' => ['label' => 'বিবরণ', 'type' => 'textarea', 'default' => 'নিজের চোখে দেখে নিন আমাদের প্রোডাক্টের গঠন, ফিনিশিং ও মজবুতি। তারপর নিশ্চিন্তে অর্ডার করুন।', 'rules' => 'nullable|string|max:500'],
                'video_thumb' => [
                    'label' => 'ভিডিওর কভার ছবি (ঐচ্ছিক)', 'type' => 'image',
                    'default' => '',
                    'rules' => $imageRules.'|dimensions:min_width=300,min_height=170,max_width=5000,max_height=5000',
                    'hint' => 'JPG, PNG বা WEBP • সর্বোচ্চ ২ MB • চওড়া (১৬:৯) ছবি ভালো। খালি রাখলে ইউটিউবের নিজস্ব ছবি দেখাবে।',
                ],
                'video_upload_date' => ['label' => 'ভিডিও আপলোডের তারিখ (গুগলের জন্য)', 'type' => 'text', 'default' => '2025-09-13', 'rules' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'], 'hint' => 'বছর-মাস-দিন ফরম্যাটে, যেমন 2025-09-13। ভিডিও বদলালে এই তারিখও বদলান। খালি রাখলে গুগলকে ভিডিওর তথ্য জানানো হবে না।'],
            ],
        ],

        'checkout_section' => [
            'title' => 'অর্ডার ফর্ম সেকশনের হেডিং',
            'icon' => 'fa-solid fa-cart-shopping',
            'fields' => [
                'checkout_pill' => ['label' => 'ছোট ব্যাজ', 'type' => 'text', 'default' => 'সহজ অর্ডার ফর্ম', 'rules' => 'nullable|string|max:80'],
                'checkout_title' => ['label' => 'শিরোনাম', 'type' => 'text', 'default' => 'অর্ডার করতে নিচের তথ্যগুলো দিয়ে ফর্ম পূরণ করুন', 'rules' => 'nullable|string|max:200'],
                'checkout_desc' => ['label' => 'বিবরণ', 'type' => 'textarea', 'default' => 'কোনো অগ্রিম পেমেন্টের প্রয়োজন নেই। ডেলিভারিম্যান পণ্য নিয়ে গেলে চেক করে সম্পূর্ণ টাকা ক্যাশ অন ডেলিভারিতে পরিশোধ করবেন।', 'rules' => 'nullable|string|max:400'],
            ],
        ],

        'reviews_section' => [
            'title' => 'রিভিউ সেকশনের হেডিং (রিভিউ যোগ করতে "রিভিউ" মেনু দেখুন)',
            'icon' => 'fa-solid fa-comments',
            'fields' => [
                'reviews_pill' => ['label' => 'ছোট ব্যাজ', 'type' => 'text', 'default' => 'গ্রাহক সন্তুষ্টি', 'rules' => 'nullable|string|max:80'],
                'reviews_title' => ['label' => 'শিরোনাম', 'type' => 'text', 'default' => 'আমাদের গ্রাহকরা কী বলছেন?', 'rules' => 'nullable|string|max:200'],
                'reviews_desc' => ['label' => 'বিবরণ', 'type' => 'text', 'default' => 'সারাদেশের হাজারো সন্তুষ্ট গ্রাহকের অভিজ্ঞতা ও তাদের রিভিউ', 'rules' => 'nullable|string|max:300'],
            ],
        ],

        'faq_section' => [
            'title' => 'FAQ সেকশনের হেডিং (প্রশ্ন যোগ করতে "FAQ" মেনু দেখুন)',
            'icon' => 'fa-solid fa-circle-question',
            'fields' => [
                'faq_pill' => ['label' => 'ছোট ব্যাজ', 'type' => 'text', 'default' => 'সাধারণ জিজ্ঞাসা', 'rules' => 'nullable|string|max:80'],
                'faq_title' => ['label' => 'শিরোনাম', 'type' => 'text', 'default' => 'প্রায়শই জিজ্ঞাসিত প্রশ্ন ও উত্তর', 'rules' => 'nullable|string|max:200'],
                'faq_desc' => ['label' => 'বিবরণ', 'type' => 'text', 'default' => 'আপনার মনের যেকোনো সাধারণ প্রশ্নের উত্তর জেনে নিন সহজেই', 'rules' => 'nullable|string|max:300'],
            ],
        ],

        'footer' => [
            'title' => 'ফুটার',
            'icon' => 'fa-solid fa-shoe-prints',
            'fields' => [
                'footer_desc' => ['label' => 'ফুটারের ব্র্যান্ড বিবরণ', 'type' => 'textarea', 'default' => 'AR Metal — দীর্ঘস্থায়ী শক্তির প্রতিশ্রুতি। আমাদের প্রতিটি মেটাল প্রোডাক্ট উন্নত প্রযুক্তি ও আধুনিক কারিগরদের তত্ত্বাবধানে ফ্যাক্টরিতে সরাসরি তৈরি হয়।', 'rules' => 'nullable|string|max:500'],
                'footer_copyright' => ['label' => 'কপিরাইট লেখা', 'type' => 'text', 'default' => '© 2026 AR Metal BD. সর্বস্বত্ব সংরক্ষিত।', 'rules' => 'nullable|string|max:200'],
            ],
        ],

        'seo' => [
            'title' => 'সার্চ ইঞ্জিন (SEO) ও সোশ্যাল শেয়ার',
            'icon' => 'fa-solid fa-magnifying-glass-chart',
            'fields' => [
                'site_title' => ['label' => 'সাইট ও এসইও মেটা টাইটেল', 'type' => 'text', 'default' => 'AR Metal BD — প্রিমিয়াম সুইং চেয়ার (দোলনা) ও মেটাল ফার্নিচার', 'rules' => 'nullable|string|max:255'],
                'meta_description' => ['label' => 'মেটা ডেসক্রিপশন (১৫০-১৬০ অক্ষর ভালো)', 'type' => 'textarea', 'default' => 'সরাসরি নিজস্ব কারখানা থেকে প্রিমিয়াম কোয়ালিটির সুইং চেয়ার (দোলনা), বেবি কট, মেটাল বেড এবং কিচেন ট্রলি। সারাদেশে ক্যাশ অন ডেলিভারি!', 'rules' => 'nullable|string|max:500'],
                'meta_image' => [
                    'label' => 'সোশ্যাল শেয়ার ইমেজ (Open Graph)', 'type' => 'image',
                    'default' => 'https://armetalbd.com/uploads/1000077471_1772519549643_rj8kri.jpg',
                    'rules' => $imageRules.'|dimensions:min_width=200,min_height=200,max_width=5000,max_height=5000',
                    'hint' => 'JPG, PNG বা WEBP • সর্বোচ্চ ২ MB • ফেসবুক শেয়ারের জন্য ১২০০×৬৩০ ভালো',
                ],
                'site_url' => ['label' => 'ওয়েবসাইটের মূল ঠিকানা (সার্চ ইঞ্জিনের জন্য)', 'type' => 'text', 'default' => '', 'rules' => 'nullable|url:http,https|max:255', 'hint' => 'যেমন: https://dolna.armetalbd.com। সাইটটি একাধিক ঠিকানায় খুললে এখানে মূল ঠিকানাটা দিন, তাহলে Google সেটাকেই আসল ধরবে। খালি রাখলে যে ঠিকানায় সাইট খোলা হয়েছে সেটাই ধরা হবে। শেষে স্ল্যাশ দেবেন না।'],
                'facebook_pixel_id' => ['label' => 'ফেসবুক পিক্সেল আইডি', 'type' => 'text', 'default' => '', 'rules' => ['nullable', 'regex:/^[0-9]{8,20}$/'], 'hint' => 'শুধু সংখ্যা (৮-২০ ডিজিট)। খালি রাখলে পিক্সেল লোড হবে না।'],
                'google_analytics_id' => ['label' => 'Google Analytics আইডি (GA4)', 'type' => 'text', 'default' => '', 'rules' => ['nullable', 'regex:/^G-[A-Z0-9]{6,15}$/'], 'hint' => 'যেমন: G-ABC123XYZ9। খালি রাখলে Analytics লোড হবে না।'],
                'clarity_project_id' => ['label' => 'Microsoft Clarity প্রজেক্ট আইডি', 'type' => 'text', 'default' => '', 'rules' => ['nullable', 'regex:/^[a-z0-9]{6,20}$/'], 'hint' => 'ক্লিক ম্যাপ ও রেকর্ডিং দেখার জন্য। খালি রাখলে লোড হবে না।'],
            ],
        ],

    ],

];
