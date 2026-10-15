{{-- Search engine and social sharing tags. Expects $seo (see App\Support\Seo) and $settings. --}}
  <title>{{ $seo['title'] }}</title>
  <meta name="description" content="{{ $seo['description'] }}">
  <meta name="robots" content="{{ $seo['robots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1' }}">
  <link rel="canonical" href="{{ $seo['canonical'] }}">
  <meta name="theme-color" content="#00804f">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="{{ $seo['type'] }}">
  <meta property="og:site_name" content="{{ $settings['brand_name'] }}">
  <meta property="og:locale" content="bn_BD">
  <meta property="og:title" content="{{ $seo['title'] }}">
  <meta property="og:description" content="{{ $seo['description'] }}">
  <meta property="og:url" content="{{ $seo['canonical'] }}">
@if(!empty($seo['image']))
  <meta property="og:image" content="{{ $seo['image'] }}">
  <meta property="og:image:alt" content="{{ $seo['imageAlt'] }}">
@endif
@foreach($seo['ogExtra'] as $property => $content)
  <meta property="{{ $property }}" content="{{ $content }}">
@endforeach

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{{ $seo['title'] }}">
  <meta name="twitter:description" content="{{ $seo['description'] }}">
@if(!empty($seo['image']))
  <meta name="twitter:image" content="{{ $seo['image'] }}">
@endif

  <!-- Structured data -->
  <script type="application/ld+json">{!! \App\Support\Seo::encode($seo['jsonLd']) !!}</script>