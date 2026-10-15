  <!-- Tracking: shared script first, so the visitor id is ready when the Pixel starts -->
  <script src="{{ asset('js/arm-core.js') }}?v={{ filemtime(public_path('js/arm-core.js')) }}"></script>
  @include('partials.meta-pixel')
  @include('partials.analytics')