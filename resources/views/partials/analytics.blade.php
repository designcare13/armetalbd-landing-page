@php
  $analyticsId = trim((string) ($settings['google_analytics_id'] ?? ''));
  $clarityId = trim((string) ($settings['clarity_project_id'] ?? ''));
@endphp
@if(preg_match('/^G-[A-Z0-9]{6,15}$/', $analyticsId))
  <!-- Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analyticsId }}"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ $analyticsId }}');
  </script>
@endif
@if(preg_match('/^[a-z0-9]{6,20}$/', $clarityId))
  <!-- Microsoft Clarity -->
  <script>
    (function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window, document, "clarity", "script", "{{ $clarityId }}");
  </script>
@endif