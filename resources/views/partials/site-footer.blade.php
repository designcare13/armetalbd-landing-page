@php
  /* Shared by the landing page and the product pages; see site-header for $anchor and $orderUrl. */
  $anchor = $anchor ?? '';
  $orderUrl = $orderUrl ?? $anchor.'#checkoutSection';
  $phone = $settings['phone'];
  $whatsappNumber = $settings['whatsapp'];
@endphp
  <!-- Site Footer -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <div class="footer-logo">
            <img src="{{ \App\Models\Setting::imageUrl($settings['logo']) }}" alt="{{ $settings['brand_name'] }}">
            <span class="footer-brand-title">{{ $settings['brand_name'] }}</span>
          </div>
          <p class="footer-desc">{{ $settings['footer_desc'] }}</p>
          <div class="footer-contact-list">
            @if($phone !== '')
            <div class="footer-contact-item">
              <i class="fa-solid fa-phone"></i>
              <span>হেল্পলাইন: {{ $phone }}</span>
            </div>
            @endif
            @if($settings['email'] !== '')
            <div class="footer-contact-item">
              <i class="fa-solid fa-envelope"></i>
              <span>ইমেইল: {{ $settings['email'] }}</span>
            </div>
            @endif
            @if($settings['address'] !== '')
            <div class="footer-contact-item">
              <i class="fa-solid fa-location-dot"></i>
              <span>ঠিকানা: {{ $settings['address'] }}</span>
            </div>
            @endif
          </div>
        </div>

        <div>
          <h4 class="footer-heading">কুইক লিংক</h4>
          <ul class="footer-links">
            @foreach($footerCategories as $cat)
            <li><a href="{{ $anchor }}#productsSection" @if($anchor === '') onclick="filterProducts('{{ $cat->slug }}')" @endif>{{ $cat->name }}</a></li>
            @endforeach
            <li><a href="{{ route('login') }}"><i class="fa-solid fa-lock"></i> অ্যাডমিন লগইন</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-heading">কাস্টমার সাপোর্ট</h4>
          <ul class="footer-links">
            @if($whatsappNumber !== '')
            <li><a href="https://api.whatsapp.com/send?phone={{ $whatsappNumber }}" target="_blank" rel="noopener">হোয়াটসঅ্যাপ চ্যাট সাপোর্ট</a></li>
            @endif
            @if($settings['facebook_url'] !== '')
            <li><a href="{{ $settings['facebook_url'] }}" target="_blank" rel="noopener">অফিসিয়াল ফেসবুক পেজ</a></li>
            @endif
            @if($settings['instagram_url'] !== '')
            <li><a href="{{ $settings['instagram_url'] }}" target="_blank" rel="noopener">ইনস্টাগ্রাম</a></li>
            @endif
            @if($settings['youtube_url'] !== '')
            <li><a href="{{ $settings['youtube_url'] }}" target="_blank" rel="noopener">ইউটিউব চ্যানেল</a></li>
            @endif
            @if($settings['tiktok_url'] !== '')
            <li><a href="{{ $settings['tiktok_url'] }}" target="_blank" rel="noopener">টিকটক</a></li>
            @endif
            @if($phone !== '')
            <li><a href="tel:{{ $phone }}">সরাসরি কল সেন্টার</a></li>
            @endif
            <li><a href="{{ $anchor }}#checkoutSection">ক্যাশ অন ডেলিভারি পলিসি</a></li>
          </ul>
        </div>
      </div>

      @if($footerProducts->isNotEmpty())
      <nav class="footer-products" aria-label="আমাদের প্রোডাক্ট">
        <h4 class="footer-heading">আমাদের প্রোডাক্ট</h4>
        <ul class="footer-product-list">
          @foreach($footerProducts as $footerProduct)
          <li><a href="{{ route('products.show', $footerProduct->slug) }}">{{ $footerProduct->name }}</a></li>
          @endforeach
        </ul>
      </nav>
      @endif

      <div class="footer-bottom">
        <div>{{ $settings['footer_copyright'] }}</div>
        <div>
          <a href="{{ route('login') }}" style="color: #64748b; font-size: 0.8rem;"><i class="fa-solid fa-shield"></i> Staff Portal</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Sticky Mobile Bottom Action Bar -->
  <div class="mobile-bottom-bar">
    <a href="tel:{{ $settings['phone'] ?? '01805962220' }}" class="mobile-bar-btn mobile-bar-call">
      <i class="fa-solid fa-phone"></i> কল করুন
    </a>
    <a href="{{ $orderUrl }}" class="mobile-bar-btn mobile-bar-order">
      <i class="fa-solid fa-bolt"></i> সরাসরি অর্ডার করুন
    </a>
  </div>

  <!-- Floating WhatsApp Button -->
  <div class="floating-actions">
    <a href="https://api.whatsapp.com/send?phone={{ $settings['whatsapp'] ?? '8801805962220' }}" target="_blank" class="float-btn float-whatsapp" title="WhatsApp Chat">
      <i class="fa-brands fa-whatsapp"></i>
    </a>
  </div>

