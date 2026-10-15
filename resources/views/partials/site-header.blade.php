@php
  /*
   * Shared by the landing page and the product pages.
   * $anchor   : '' on the landing page, the landing page address elsewhere (so #links still work)
   * $orderUrl : where the order buttons lead
   */
  $anchor = $anchor ?? '';
  $orderUrl = $orderUrl ?? $anchor.'#checkoutSection';
  $brandParts = preg_split('/\s+/u', trim($settings['brand_name']), 2);
@endphp
  <!-- Top Announcement Bar -->
  <aside class="top-bar" aria-label="Announcement">
    <div class="container">
      <div class="top-info">
        <a href="tel:{{ $settings['phone'] ?? '01805962220' }}"><i class="fa-solid fa-phone-volume"></i> {{ $settings['phone'] ?? '01805962220' }}</a>
        <a href="https://api.whatsapp.com/send?phone={{ $settings['whatsapp'] ?? '8801805962220' }}" target="_blank"><i class="fa-brands fa-whatsapp"></i> হোয়াটসঅ্যাপে মেসেজ দিন</a>
        <span><i class="fa-solid fa-location-dot"></i> {{ $settings['address'] ?? 'মিরপুর, কালশী কবরস্থান, ঢাকা-১২১৬' }}</span>
      </div>
      <div>
        @if($settings['topbar_badge'] !== '')<span class="top-badge"><i class="fa-solid fa-truck-fast"></i> {{ $settings['topbar_badge'] }}</span>@endif
      </div>
    </div>
  </aside>

  <!-- Sticky Site Header -->
  <header class="site-header">
    <div class="container">
      <a href="{{ url('/') }}" class="brand-logo">
        <img src="{{ \App\Models\Setting::imageUrl($settings['logo']) }}" alt="{{ $settings['brand_name'] }}">
        <div class="brand-text">
          <span class="brand-name">{{ $brandParts[0] }}@if(!empty($brandParts[1])) <span>{{ $brandParts[1] }}</span>@endif</span>
          <span class="brand-tagline">{{ $settings['brand_tagline'] }}</span>
        </div>
      </a>

      <div class="header-actions">
        <a href="tel:{{ $settings['phone'] ?? '01805962220' }}" class="call-pill d-none-sm">
          <i class="fa-solid fa-phone"></i> {{ $settings['phone'] ?? '০১৮০৫৯৬২২২০' }}
        </a>
        <a href="{{ $orderUrl }}" class="cart-icon-btn">
          <i class="fa-solid fa-cart-shopping"></i>
          <span>অর্ডার লিস্ট</span>
          <span class="cart-badge" id="headerCartCount">0</span>
        </a>
        <a href="{{ $orderUrl }}" class="btn btn-primary" style="padding: 10px 20px; font-size: 0.95rem;">
          <i class="fa-solid fa-bolt"></i> অর্ডার করুন
        </a>
      </div>
    </div>
  </header>

