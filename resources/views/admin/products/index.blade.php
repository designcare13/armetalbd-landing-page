@extends('admin.layout')

@section('title', 'প্রোডাক্ট তালিকা')
@section('page_title', 'প্রোডাক্ট ম্যানেজমেন্ট')

@section('content')

<style>
  .product-tools { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; }
  .product-search { display: flex; gap: 8px; }
  .product-search .form-control { width: 280px; padding: 8px 12px; font-size: 0.88rem; }
  .stock-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; font-size: 0.8rem; font-weight: 700; font-family: var(--font-en); white-space: nowrap; }
  .stock-ok { background: #dcfce7; color: #15803d; }
  .stock-low { background: #fef3c7; color: #b45309; }
  .stock-out { background: #fee2e2; color: #b91c1c; }
  @media (max-width: 700px) {
    .product-search { width: 100%; }
    .product-search .form-control { flex: 1; width: auto; }
  }
</style>

<div class="card">
  <div class="card-title">
    <span><i class="fa-solid fa-box-open"></i> {{ $search !== '' ? 'খোঁজার ফলাফল' : 'সমস্ত প্রোডাক্ট তালিকা' }} ({{ $products->total() }} টি)</span>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-plus"></i> নতুন প্রোডাক্ট যোগ করুন
    </a>
  </div>

  <div class="product-tools">
    <form action="{{ route('admin.products.index') }}" method="GET" class="product-search">
      @if(request('per_page'))
        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
      @endif
      <input type="text" name="search" class="form-control" placeholder="প্রোডাক্ট বা ক্যাটাগরির নাম..." value="{{ $search }}">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-magnifying-glass"></i> খুঁজুন
      </button>
      @if($search !== '')
        <a href="{{ route('admin.products.index', request()->only('per_page')) }}" class="btn btn-secondary btn-sm" title="ক্লিয়ার করুন">
          <i class="fa-solid fa-xmark"></i>
        </a>
      @endif
    </form>
  </div>

  @if($products->isEmpty())
    <div style="text-align: center; padding: 40px; color: #64748b;">
      <i class="fa-solid fa-magnifying-glass" style="font-size: 2.5rem; margin-bottom: 12px; display: block; color: #cbd5e1;"></i>
      কোনো প্রোডাক্ট পাওয়া যায়নি।
    </div>
  @else
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>ছবি</th>
          <th>প্রোডাক্টের নাম</th>
          <th>ক্যাটাগরি</th>
          <th>মূল্য</th>
          <th>রেগুলার মূল্য</th>
          <th>স্টক</th>
          <th>ব্যাজ</th>
          <th>স্ট্যাটাস</th>
          <th>অ্যাকশন</th>
        </tr>
      </thead>
      <tbody>
        @foreach($products as $prod)
        <tr>
          <td style="width: 70px;">
            <img src="{{ $prod->image_url }}" alt="{{ $prod->name }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
          </td>
          <td>
            <strong>{{ $prod->name }}</strong>
            @if($prod->is_featured)
              <span style="background: #fef3c7; color: #b45309; font-size: 0.72rem; padding: 2px 6px; border-radius: 4px; font-weight: 700;">ফিচার্ড</span>
            @endif
          </td>
          <td>
            <span style="font-size: 0.85rem; color: #64748b;">
              {{ $prod->category->name ?? 'জেনারেল' }}
            </span>
          </td>
          <td>
            <strong style="color: var(--primary); font-family: var(--font-en);">৳{{ number_format($prod->price) }}</strong>
          </td>
          <td>
            @if($prod->old_price)
              <span style="color: #94a3b8; text-decoration: line-through; font-family: var(--font-en);">৳{{ number_format($prod->old_price) }}</span>
            @else
              -
            @endif
          </td>
          <td>
            @if($prod->stock <= 0)
              <span class="stock-badge stock-out"><i class="fa-solid fa-circle-xmark"></i> স্টক শেষ</span>
            @elseif($prod->stock <= \App\Models\Product::LOW_STOCK_LIMIT)
              <span class="stock-badge stock-low" title="স্টক কমে গেছে"><i class="fa-solid fa-triangle-exclamation"></i> {{ $prod->stock }} টি</span>
            @else
              <span class="stock-badge stock-ok">{{ $prod->stock }} টি</span>
            @endif
          </td>
          <td>
            @if($prod->badge)
              <span style="background: #e2e8f0; color: #334155; padding: 2px 8px; border-radius: 999px; font-size: 0.75rem; font-weight: 700;">
                {{ $prod->badge }}
              </span>
            @else
              -
            @endif
          </td>
          <td>
            @if($prod->is_active)
              <span class="status-badge status-delivered">সক্রিয়</span>
            @else
              <span class="status-badge status-cancelled">নিষ্ক্রিয়</span>
            @endif
          </td>
          <td>
            <div style="display: flex; gap: 6px;">
              <a href="{{ route('admin.products.edit', $prod) }}" class="btn btn-secondary btn-sm" title="এডিট করুন">
                <i class="fa-solid fa-pen-to-square"></i>
              </a>
              <form action="{{ route('admin.products.destroy', $prod) }}" method="POST" data-confirm="আপনি কি নিশ্চিত এই প্রোডাক্টটি মুছে ফেলতে চান?" style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" title="মুছে ফেলুন">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @endif

  @include('admin._list-footer', ['paginator' => $products])
</div>

@endsection
