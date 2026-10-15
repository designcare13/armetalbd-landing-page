<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  @include('seo.meta')
  <link rel="preload" as="image" href="{{ $product->image_url }}" fetchpriority="high">

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
  @include('partials.site-header', ['anchor' => route('home'), 'orderUrl' => $orderUrl])

  <main class="pp-main">
    <div class="container">
      <nav class="pp-breadcrumb" aria-label="breadcrumb">
        <ol>
          <li><a href="{{ route('home') }}">হোম</a></li>
          <li aria-current="page">{{ $product->name }}</li>
        </ol>
      </nav>

      @php
        $inStock = $product->stock > 0;
        $body = $product->description ?: $product->short_description;
        $lead = $product->description ? $product->short_description : null;
        $savings = $product->old_price && $product->old_price > $product->price ? $product->old_price - $product->price : 0;
      @endphp

      <article class="pp-layout">
        <div class="pp-media">
          @if($product->badge)<span class="pp-badge"><i class="fa-solid fa-fire"></i> {{ $product->badge }}</span>@endif
          <img src="{{ $product->image_url }}" alt="{{ $product->name }}" fetchpriority="high" decoding="async">
        </div>

        <div class="pp-info">
          @if($product->category)<span class="section-pill">{{ $product->category->name }}</span>@endif
          <h1 class="pp-title">{{ $product->name }}</h1>

          <div class="pp-meta">
            <span class="card-stars">
              @for($star = 0; $star < (int) floor($product->rating); $star++)<i class="fa-solid fa-star"></i>@endfor
              <span>({{ $product->reviews_count }} রিভিউ)</span>
            </span>
            <span class="pp-stock {{ $inStock ? 'in' : 'out' }}">
              <i class="fa-solid {{ $inStock ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i> {{ $inStock ? 'স্টকে আছে' : 'স্টক শেষ' }}
            </span>
          </div>

          <div class="pp-price-row">
            <span class="price-current">৳{{ number_format($product->price) }}</span>
            @if($product->old_price)<span class="price-old">৳{{ number_format($product->old_price) }}</span>@endif
            @if($product->discount_label)<span class="badge badge-discount">{{ $product->discount_label }}</span>@endif
          </div>
          @if($savings)<p class="pp-saving"><i class="fa-solid fa-tag"></i> আপনার সাশ্রয়: ৳{{ number_format($savings) }}</p>@endif

          @if($lead)<p class="pp-lead">{{ $lead }}</p>@endif

          <div class="pp-actions">
            @if($inStock)
              <a href="{{ $orderUrl }}" class="btn btn-primary"><i class="fa-solid fa-bolt"></i> এখনই অর্ডার করুন</a>
            @else
              <span class="btn btn-primary pp-disabled" aria-disabled="true">স্টক শেষ</span>
            @endif
            @if($settings['phone'] !== '')
              <a href="tel:{{ $settings['phone'] }}" class="btn btn-secondary"><i class="fa-solid fa-phone"></i> কল করুন</a>
            @endif
            @if($settings['whatsapp'] !== '')
              <a href="{{ $whatsappUrl }}" class="btn btn-secondary" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
            @endif
          </div>

          <ul class="pp-trust">
            <li><i class="fa-solid fa-hand-holding-dollar"></i> পণ্য হাতে পেয়ে টাকা দিন, ক্যাশ অন ডেলিভারি</li>
            <li><i class="fa-solid fa-truck-fast"></i> ঢাকায় ও সারা দেশে ডেলিভারি</li>
            <li><i class="fa-solid fa-industry"></i> সরাসরি নিজস্ব কারখানা থেকে</li>
          </ul>
        </div>
      </article>

      @if($body)
      <section class="pp-section">
        <h2 class="pp-heading">প্রোডাক্টের বিবরণ</h2>
        <div class="pp-text">{{ $body }}</div>
      </section>
      @endif

      @if(count($specRows))
      <section class="pp-section">
        <h2 class="pp-heading">প্রোডাক্টের বৈশিষ্ট্যসমূহ</h2>
        <div class="pp-specs">
          @foreach($specRows as $row)
            @if($row['label'])
              <div class="pp-spec-row"><span class="pp-spec-key">{{ $row['label'] }}</span><span class="pp-spec-val">{{ $row['value'] }}</span></div>
            @else
              <div class="pp-spec-note"><i class="fa-solid fa-check"></i> {{ $row['value'] }}</div>
            @endif
          @endforeach
        </div>
      </section>
      @endif

      @if($related->isNotEmpty())
      <section class="pp-section">
        <h2 class="pp-heading">আরও প্রোডাক্ট দেখুন</h2>
        <div class="pp-related">
          @foreach($related as $item)
          <a href="{{ route('products.show', $item->slug) }}" class="pp-related-card">
            <img src="{{ $item->image_url }}" alt="{{ $item->name }}" loading="lazy" decoding="async">
            <span class="pp-related-name">{{ $item->name }}</span>
            <span class="pp-related-price">৳{{ number_format($item->price) }}</span>
          </a>
          @endforeach
        </div>
      </section>
      @endif
    </div>
  </main>

  @include('partials.site-footer', ['anchor' => route('home'), 'orderUrl' => $orderUrl])

  @php
    $viewContent = [
      'content_name' => $product->name,
      'content_ids' => [(string) $product->id],
      'content_type' => 'product',
      'contents' => [['id' => (string) $product->id, 'quantity' => 1, 'item_price' => (float) $product->price]],
      'value' => (float) $product->price,
      'currency' => 'BDT',
    ];
  @endphp
  <script>if (window.ArmTracking) { ArmTracking.track('ViewContent', @json($viewContent)); }</script>
</body>
</html>