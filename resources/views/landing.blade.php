<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  
  @include('seo.meta')
  <link rel="preload" as="image" href="{{ \App\Models\Setting::imageUrl($settings['hero_image']) }}" fetchpriority="high">

  <!-- Favicon -->
  <link rel="shortcut icon" href="{{ \App\Models\Setting::imageUrl($settings['favicon']) }}">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- Main Stylesheet -->
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">

  @include('partials.tracking')
</head>
<body>
@php
  $lines = fn ($text) => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text))));
  $img = fn ($key) => \App\Models\Setting::imageUrl($settings[$key]);

@endphp

  @include('partials.site-header')

  <main>
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="hero-grid">
          <div class="hero-content">
            @if($settings['hero_pill'] !== '')
            <span class="hero-pill"><i class="fa-solid fa-certificate"></i> {{ $settings['hero_pill'] }}</span>
            @endif
            <h1 class="hero-title">
              {{ $settings['hero_title'] }} <span class="highlight">{{ $settings['hero_highlight'] }}</span> {{ $settings['hero_title_after'] }}
            </h1>
            <p class="hero-desc">{{ $settings['hero_desc'] }}</p>

            <div class="hero-points">
              @foreach($lines($settings['hero_points']) as $point)
              <div class="hero-point-item">
                <i class="fa-solid fa-check"></i>
                <span>{{ $point }}</span>
              </div>
              @endforeach
            </div>

            <div class="hero-cta-group">
              <a href="#checkoutSection" class="btn btn-primary btn-pulse">
                <i class="fa-solid fa-cart-plus"></i> সরাসরি অর্ডার করুন
              </a>
              <a href="#productsSection" class="btn btn-secondary">
                <i class="fa-solid fa-layer-group"></i> সব প্রোডাক্ট দেখুন
              </a>
            </div>
          </div>

          <div class="hero-image-wrap">
            <div class="hero-main-card">
              @if($settings['hero_badge'] !== '')
              <div class="hero-img-badge">
                <span class="dot"></span>
                <span>{{ $settings['hero_badge'] }}</span>
              </div>
              @endif
              <img src="{{ $img('hero_image') }}" alt="{{ $settings['hero_card_title'] }}" fetchpriority="high" decoding="async">
              <div class="hero-card-bottom">
                <div>
                  <h3 class="hero-card-title">{{ $settings['hero_card_title'] }}</h3>
                  <p class="hero-card-sub">{{ $settings['hero_card_sub'] }}</p>
                </div>
                <div class="hero-card-price">
                  <div class="hero-card-old-price">{{ $settings['hero_card_old_price'] }}</div>
                  <div class="hero-card-new-price">{{ $settings['hero_card_new_price'] }}</div>
                </div>
              </div>
            </div>

            <div class="hero-floating-stat">
              <div class="floating-icon">
                <i class="fa-solid fa-medal"></i>
              </div>
              <div>
                <div class="floating-stat-num">{{ $settings['hero_stat_num'] }}</div>
                <div class="floating-stat-text">{{ $settings['hero_stat_text'] }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Trust Badges Strip -->
    <section class="trust-bar">
      <div class="container">
        <div class="trust-grid">
          @foreach([1 => 'fa-hand-holding-dollar', 2 => 'fa-industry', 3 => 'fa-shield-halved', 4 => 'fa-truck-ramp-box'] as $n => $icon)
          @if($settings["trust_{$n}_title"] !== '')
          <div class="trust-item">
            <div class="trust-icon-box"><i class="fa-solid {{ $icon }}"></i></div>
            <div>
              <h4 class="trust-title">{{ $settings["trust_{$n}_title"] }}</h4>
              <p class="trust-desc">{{ $settings["trust_{$n}_desc"] }}</p>
            </div>
          </div>
          @endif
          @endforeach
        </div>
      </div>
    </section>

    <!-- Multi-Products Showcase Section -->
    <section class="products-section" id="productsSection">
      <div class="container">
        <div class="section-header">
          <span class="section-pill">{{ $settings['products_pill'] }}</span>
          <h2 class="section-title">{{ $settings['products_title'] }}</h2>
          <p class="section-desc">{{ $settings['products_desc'] }}</p>
        </div>

        <!-- Filter Tabs -->
        <div class="category-filter-wrap">
          <button class="filter-btn active" data-category="all" onclick="filterProducts('all')">
            <i class="fa-solid fa-border-all"></i> সবগুলো প্রোডাক্ট <span class="count">{{ $products->count() }}</span>
          </button>
          @foreach($categories as $cat)
          <button class="filter-btn" data-category="{{ $cat->slug }}" onclick="filterProducts('{{ $cat->slug }}')">
            {{ $cat->name }} <span class="count">{{ $cat->products_count }}</span>
          </button>
          @endforeach
        </div>

        <!-- Dynamic Product Cards Grid -->
        <div class="products-grid" id="productsGrid">
          <!-- Injected via JavaScript from database JSON -->
        </div>
      </div>
    </section>

    <!-- Spotlight Section: Why AR Metal Swing Chairs are Superior -->
    <section class="spotlight-section">
      <div class="container">
        <div class="spotlight-grid">
          <div>
            <span class="spotlight-pill"><i class="fa-solid fa-sparkles"></i> {{ $settings['spotlight_pill'] }}</span>
            <h2 class="spotlight-title">{{ $settings['spotlight_title'] }}</h2>
            <p class="spotlight-desc">{{ $settings['spotlight_desc'] }}</p>

            <div class="spotlight-features">
              @foreach([1 => 'fa-weight-hanging', 2 => 'fa-paint-roller', 3 => 'fa-chair', 4 => 'fa-screwdriver-wrench'] as $n => $icon)
              @if($settings["spotlight_{$n}_title"] !== '')
              <div class="feature-box">
                <i class="fa-solid {{ $icon }}"></i>
                <h4>{{ $settings["spotlight_{$n}_title"] }}</h4>
                <p>{{ $settings["spotlight_{$n}_desc"] }}</p>
              </div>
              @endif
              @endforeach
            </div>

            <a href="#checkoutSection" class="btn btn-primary">
              <i class="fa-solid fa-cart-shopping"></i> এখনই ডিসকাউন্ট মূল্যে অর্ডার করুন
            </a>
          </div>

          <div class="spotlight-gallery">
            <div class="spotlight-gallery-card">
              <img src="{{ $img('spotlight_image') }}" alt="{{ $settings['spotlight_title'] }}" loading="lazy" decoding="async">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Multi-Product Interactive Checkout Section -->
    <section class="checkout-section" id="checkoutSection">
      <div class="container">
        <div class="section-header">
          <span class="section-pill">{{ $settings['checkout_pill'] }}</span>
          <h2 class="section-title">{{ $settings['checkout_title'] }}</h2>
          <p class="section-desc">{{ $settings['checkout_desc'] }}</p>
        </div>

        <div class="checkout-grid">
          <!-- Left Column: Selected Products Box & Customer Details -->
          <div class="checkout-card">
            <div class="card-step-header">
              <span class="step-num">১</span>
              <div>
                <h3 class="step-title">আপনার নির্বাচিত প্রোডাক্ট সমূহ (<span id="orderCountText">0 টি আইটেম</span>)</h3>
                <p class="step-sub">প্রয়োজনে পরিমাণ বাড়াতে/কমাতে (+ / -) বা অন্য প্রোডাক্ট যোগ করতে পারেন</p>
              </div>
            </div>

            <!-- Selected Items Box -->
            <div class="selected-products-box">
              <div id="selectedItemsList" class="selected-items-list">
                <!-- Injected via JavaScript -->
              </div>
              <div id="emptyCartNotice" class="empty-cart-notice" style="display: none;">
                <i class="fa-solid fa-basket-shopping"></i>
                <p>আপনার কোনো প্রোডাক্ট সিলেক্ট করা নেই!</p>
                <a href="#productsSection" class="btn btn-secondary" style="margin-top: 10px; padding: 8px 16px; font-size: 0.9rem;">
                  প্রোডাক্ট সিলেক্ট করুন
                </a>
              </div>
            </div>

            <form id="orderForm" onsubmit="handleOrderSubmit(event)" novalidate>
              <div class="card-step-header" style="margin-top: 30px;">
                <span class="step-num">২</span>
                <div>
                  <h3 class="step-title">ডেলিভারির ঠিকানা ও তথ্য</h3>
                  <p class="step-sub">সঠিক নাম ও ফোন নম্বর দিন যাতে ডেলিভারির সময় আপনাকে কল দেওয়া যায়</p>
                </div>
              </div>

              <div class="form-group">
                <label for="customerName" class="form-label">আপনার নাম <span class="req">*</span></label>
                <input type="text" id="customerName" class="form-control" placeholder="যেমন: মোঃ আরিফুল ইসলাম" required>
              </div>

              <div class="form-group">
                <label for="customerPhone" class="form-label">মোবাইল নম্বর <span class="req">*</span></label>
                <input type="tel" id="customerPhone" class="form-control" placeholder="যেমন: 01805962220" required>
              </div>

              <p class="form-privacy-note">
                <i class="fa-solid fa-shield-halved"></i>
                আপনার নম্বর দিলে অর্ডার সম্পন্ন না হলেও আমাদের প্রতিনিধি ফোন করে সহায়তা করতে পারেন। বিজ্ঞাপনের ফল মাপতে আপনার অর্ডার ও ফোন নম্বর (কোড করা অবস্থায়) Meta বা Facebook-কে জানানো হতে পারে। আপনার তথ্য গোপন রাখা হবে এবং ইনকমপ্লিট তথ্য নির্দিষ্ট সময় পর মুছে ফেলা হবে।
              </p>

              <div class="form-group">
                <label for="customerAddress" class="form-label">সম্পূর্ণ ঠিকানা (বাসা/রোড, এলাকা, জেলা) <span class="req">*</span></label>
                <textarea id="customerAddress" rows="3" class="form-control" placeholder="যেমন: বাড়ি নং ১২, রোড ৫, মিরপুর-১০, ঢাকা" required></textarea>
              </div>

              <div class="form-group">
                <label class="form-label">ডেলিভারি এরিয়া নির্বাচন করুন <span class="req">*</span></label>
                <div class="delivery-options-grid">
                  <label class="delivery-radio-label selected" id="del-inside" onclick="setDelivery('inside', {{ (int)($settings['delivery_inside'] ?? 150) }})">
                    <input type="radio" name="deliveryArea" value="inside" checked>
                    <span class="delivery-area-title"><i class="fa-solid fa-city"></i> ঢাকা সিটির ভেতরে</span>
                    <span class="delivery-charge">চার্জ: ৳{{ $settings['delivery_inside'] ?? 150 }}</span>
                  </label>

                  <label class="delivery-radio-label" id="del-outside" onclick="setDelivery('outside', {{ (int)($settings['delivery_outside'] ?? 300) }})">
                    <input type="radio" name="deliveryArea" value="outside">
                    <span class="delivery-area-title"><i class="fa-solid fa-map-location-dot"></i> ঢাকার বাইরে (সমগ্র বাংলাদেশ)</span>
                    <span class="delivery-charge">চার্জ: ৳{{ $settings['delivery_outside'] ?? 300 }}</span>
                  </label>
                </div>
              </div>

              <div class="form-group">
                <label for="customerNote" class="form-label">বিশেষ কোনো নির্দেশনা থাকলে লিখুন (অপশনাল)</label>
                <input type="text" id="customerNote" class="form-control" placeholder="যেমন: কালারের পছন্দ বা কোনো ডেলিভারি সময়...">
              </div>

              <button type="submit" class="btn-submit-order btn-pulse" id="btnSubmitOrder">
                <i class="fa-solid fa-check-circle"></i> অর্ডার নিশ্চিত করুন (ক্যাশ অন ডেলিভারি)
              </button>

              <div class="order-guarantee-note">
                <i class="fa-solid fa-lock"></i> ১০০% নিরাপদ কেনাকাটা • পণ্য দেখে মূল্য পরিশোধ করার নিশ্চয়তা
              </div>
            </form>
          </div>

          <!-- Right Column: Order Summary Box -->
          <div>
            <div class="summary-box">
              <h3 class="summary-title"><i class="fa-solid fa-receipt"></i> অর্ডারের বিবরণী</h3>

              <div class="summary-row">
                <span>প্রোডাক্ট সাব-টোটাল</span>
                <span id="summarySubtotal" style="font-weight: 700; font-family: var(--font-en);">৳০</span>
              </div>

              <div class="summary-row">
                <span>ডেলিভারি চার্জ</span>
                <span id="summaryDelivery" style="font-weight: 700; font-family: var(--font-en);">৳{{ $settings['delivery_inside'] ?? 150 }}</span>
              </div>

              <div class="summary-row total">
                <span>সর্বমোট প্রদেয় বিল</span>
                <span class="total-amount" id="summaryTotal">৳০</span>
              </div>

              <div class="payment-method-box">
                <i class="fa-solid fa-money-bill-wave"></i>
                <div>
                  <div class="payment-method-title">ক্যাশ অন ডেলিভারি (COD)</div>
                  <div class="payment-method-sub">প্রোডাক্ট হাতে পাওয়ার পর মূল্য দিন</div>
                </div>
              </div>

              <div style="background: var(--primary-light); border-radius: var(--radius-md); padding: 14px; font-size: 0.88rem; color: var(--primary-dark); line-height: 1.5;">
                <i class="fa-solid fa-circle-info"></i> <strong>জরুরি তথ্য:</strong> অর্ডার করার পর আমাদের কাস্টমার কেয়ার থেকে আপনাকে ফোন করে অর্ডারটি কনফার্ম করা হবে।
              </div>

              <div style="margin-top: 20px; text-align: center;">
                <span style="font-size: 0.85rem; color: var(--gray);">ফোনে সরাসরি অর্ডার করতে চাইলে:</span>
                <div style="margin-top: 6px;">
                  <a href="tel:{{ $settings['phone'] ?? '01805962220' }}" style="font-size: 1.25rem; font-weight: 700; color: var(--primary); font-family: var(--font-en); display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-phone-flip"></i> {{ $settings['phone'] ?? '01805962220' }}
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Customer Reviews Section -->
    @if($reviews->isNotEmpty())
    <section class="reviews-section">
      <div class="container">
        <div class="section-header">
          <span class="section-pill">{{ $settings['reviews_pill'] }}</span>
          <h2 class="section-title">{{ $settings['reviews_title'] }}</h2>
          <p class="section-desc">{{ $settings['reviews_desc'] }}</p>
        </div>

        <div class="reviews-grid">
          @foreach($reviews as $review)
          <div class="review-card">
            <div class="review-stars">
              @for($i = 0; $i < $review->rating; $i++)<i class="fa-solid fa-star"></i>@endfor
            </div>
            <p class="review-text">"{{ $review->body }}"</p>
            <div class="reviewer-profile">
              <div class="reviewer-avatar">{{ \Illuminate\Support\Str::substr($review->name, 0, 2) }}</div>
              <div>
                <div class="reviewer-name">{{ $review->name }}</div>
                @if($review->location)<div class="reviewer-location">{{ $review->location }}</div>@endif
              </div>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </section>
    @endif

    <!-- FAQ Section -->
    @if($faqs->isNotEmpty())
    <section class="faq-section">
      <div class="container">
        <div class="section-header">
          <span class="section-pill">{{ $settings['faq_pill'] }}</span>
          <h2 class="section-title">{{ $settings['faq_title'] }}</h2>
          <p class="section-desc">{{ $settings['faq_desc'] }}</p>
        </div>

        <div class="faq-container">
          @foreach($faqs as $faq)
          <div class="faq-item {{ $loop->first ? 'active' : '' }}">
            <div class="faq-header">
              <span>{{ $faq->question }}</span>
              <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-body">{{ $faq->answer }}</div>
          </div>
          @endforeach
        </div>
      </div>
    </section>
    @endif
  </main>

  @include('partials.site-footer')

  <!-- Quick View Modal -->
  <div class="modal-overlay pv-overlay" id="quickViewModal" onclick="if(event.target===this) closeModal('quickViewModal')">
    <div class="pv-dialog" role="dialog" aria-modal="true" aria-label="প্রোডাক্টের বিস্তারিত">
      <button type="button" class="pv-close" onclick="closeModal('quickViewModal')" aria-label="বন্ধ করুন">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="pv-grid" id="quickViewContent">
        <!-- Injected via JavaScript -->
      </div>
    </div>
  </div>

  <!-- Order Success Confirmation Modal -->
  <div class="modal-overlay" id="orderSuccessModal">
    <div class="modal-dialog">
      <div class="success-modal-content">
        <div class="success-icon">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <h2 class="success-title">ধন্যবাদ! আপনার অর্ডারটি গৃহীত হয়েছে</h2>
        <p class="success-desc">
          আপনার অর্ডারটি সফলভাবে ডাটাবেজে রেকর্ড করা হয়েছে। আমাদের প্রতিনিধি অতি দ্রুত আপনাকে ফোন করে ডেলিভারি কনফার্ম করবেন।
        </p>

        <div class="success-order-details">
          <div class="order-detail-line">
            <strong>অর্ডার নম্বর:</strong>
            <span id="confirmOrderId" style="font-family: var(--font-en); color: var(--primary); font-weight: 700;"></span>
          </div>
          <div class="order-detail-line">
            <strong>গ্রাহকের নাম:</strong>
            <span id="confirmCustomerName"></span>
          </div>
          <div class="order-detail-line">
            <strong>মোবাইল নম্বর:</strong>
            <span id="confirmCustomerPhone" style="font-family: var(--font-en);"></span>
          </div>
          <div class="order-detail-line">
            <strong>ডেলিভারি ঠিকানা:</strong>
            <span id="confirmAddress"></span>
          </div>
          <div class="order-detail-line">
            <strong>নির্বাচিত প্রোডাক্ট:</strong>
            <span id="confirmProductsList"></span>
          </div>
          <div class="order-detail-line">
            <strong>ডেলিভারি এরিয়া:</strong>
            <span id="confirmDeliveryArea"></span>
          </div>
          <div class="order-detail-line" style="border-top: 1px dashed var(--gray-light); padding-top: 8px; margin-top: 8px;">
            <strong>সর্বমোট বিল (ক্যাশ অন ডেলিভারি):</strong>
            <span id="confirmGrandTotal" style="font-size: 1.2rem; font-weight: 700; color: var(--primary); font-family: var(--font-en);"></span>
          </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('orderSuccessModal'); window.location.reload();">
            <i class="fa-solid fa-house"></i> হোমপেজে ফিরে যান
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Dynamic Product Catalog from Database -->
  <script>
    window.PRODUCTS_DATA = @json($productsFormatted);

    window.STORE_CONFIG = {
        insideCharge: {{ (int)($settings['delivery_inside'] ?? 150) }},
        outsideCharge: {{ (int)($settings['delivery_outside'] ?? 300) }},
        csrfToken: '{{ csrf_token() }}',
        orderUrl: '{{ route('landing.order') }}',
        incompleteUrl: '{{ route('landing.incomplete') }}',
        phone: @json($settings['phone'] ?? '01805962220'),
        pixelId: '{{ $settings['facebook_pixel_id'] ?? '' }}'
    };
  </script>

  <!-- JavaScript App Logic -->
  <script src="{{ asset('js/landing.js') }}?v={{ filemtime(public_path('js/landing.js')) }}"></script>
</body>
</html>
