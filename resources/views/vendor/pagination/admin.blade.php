@if ($paginator->hasPages())
<nav class="pager" role="navigation" aria-label="পেজিনেশন">
  <div class="pager-info">
    মোট <strong>{{ $paginator->total() }}</strong> টির মধ্যে
    <strong>{{ $paginator->firstItem() }}-{{ $paginator->lastItem() }}</strong> দেখানো হচ্ছে
  </div>

  <ul class="pager-list">
    @if ($paginator->onFirstPage())
      <li class="disabled" aria-disabled="true"><span><i class="fa-solid fa-chevron-left"></i> আগের</span></li>
    @else
      <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i> আগের</a></li>
    @endif

    @foreach ($elements as $element)
      @if (is_string($element))
        <li class="disabled"><span>{{ $element }}</span></li>
      @endif

      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            <li class="active" aria-current="page"><span>{{ $page }}</span></li>
          @else
            <li><a href="{{ $url }}">{{ $page }}</a></li>
          @endif
        @endforeach
      @endif
    @endforeach

    @if ($paginator->hasMorePages())
      <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">পরের <i class="fa-solid fa-chevron-right"></i></a></li>
    @else
      <li class="disabled" aria-disabled="true"><span>পরের <i class="fa-solid fa-chevron-right"></i></span></li>
    @endif
  </ul>
</nav>
@endif
