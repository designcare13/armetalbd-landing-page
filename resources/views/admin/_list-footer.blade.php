{{-- Shared footer of the admin lists: row count, page links and the rows-per-page choice. Needs $paginator. --}}
@if($paginator->total() > 0)
  @php
    $keptQuery = collect(request()->except(['per_page', 'page']))->filter(fn ($value) => is_scalar($value));
  @endphp

  <div class="list-footer">
    @if($paginator->hasPages())
      {{ $paginator->links() }}
    @else
      <div class="pager-info">মোট <strong>{{ $paginator->total() }}</strong> টি</div>
    @endif

    <form method="GET" action="{{ url()->current() }}" class="per-page">
      @foreach($keptQuery as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
      @endforeach
      <label for="per_page">প্রতি পেজে</label>
      <select name="per_page" id="per_page" onchange="this.form.submit()">
        @foreach(\App\Http\Controllers\Controller::PER_PAGE_OPTIONS as $option)
          <option value="{{ $option }}" @selected($paginator->perPage() === $option)>{{ $option }} টি</option>
        @endforeach
      </select>
      <noscript><button type="submit" class="btn btn-secondary btn-sm">দেখুন</button></noscript>
    </form>
  </div>
@endif
