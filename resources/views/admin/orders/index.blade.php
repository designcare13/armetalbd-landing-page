@extends('admin.layout')

@section('title', 'অর্ডার তালিকা')
@section('page_title', 'অর্ডার ম্যানেজমেন্ট')

@section('content')

<div class="card">
  <!-- Search & Filter Bar -->
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <!-- Filter Pills -->
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
      <a href="{{ route('admin.orders.index') }}" class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-secondary' }}">
        সকল অর্ডার
      </a>
      <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="btn btn-sm {{ request('status') === 'pending' ? 'btn-primary' : 'btn-secondary' }}">
        পেন্ডিং
      </a>
      <a href="{{ route('admin.orders.index', ['status' => 'confirmed']) }}" class="btn btn-sm {{ request('status') === 'confirmed' ? 'btn-primary' : 'btn-secondary' }}">
        কনফার্মড
      </a>
      <a href="{{ route('admin.orders.index', ['status' => 'shipped']) }}" class="btn btn-sm {{ request('status') === 'shipped' ? 'btn-primary' : 'btn-secondary' }}">
        শিপড
      </a>
      <a href="{{ route('admin.orders.index', ['status' => 'delivered']) }}" class="btn btn-sm {{ request('status') === 'delivered' ? 'btn-primary' : 'btn-secondary' }}">
        ডেলিভার্ড
      </a>
      <a href="{{ route('admin.orders.index', ['status' => 'cancelled']) }}" class="btn btn-sm {{ request('status') === 'cancelled' ? 'btn-primary' : 'btn-secondary' }}">
        বাতিল
      </a>
    </div>

    <!-- Search Form -->
    <form action="{{ route('admin.orders.index') }}" method="GET" style="display: flex; gap: 8px;">
      @if(request('status'))
        <input type="hidden" name="status" value="{{ request('status') }}">
      @endif
      <input type="text" name="search" class="form-control" placeholder="অর্ডার নং, নাম বা ফোন..." value="{{ request('search') }}" style="width: 220px; padding: 6px 12px; font-size: 0.88rem;">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-magnifying-glass"></i> খুঁজুন
      </button>
      @if(request('search'))
        <a href="{{ route('admin.orders.index', request('status') ? ['status' => request('status')] : []) }}" class="btn btn-secondary btn-sm" title="ক্লিয়ার করুন">
          <i class="fa-solid fa-xmark"></i>
        </a>
      @endif
    </form>
  </div>

  @if($orders->isEmpty())
    <div style="text-align: center; padding: 40px; color: #64748b;">
      <i class="fa-solid fa-filter-circle-xmark" style="font-size: 2.5rem; margin-bottom: 12px; display: block; color: #cbd5e1;"></i>
      কোনো অর্ডার পাওয়া যায়নি।
    </div>
  @else
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>অর্ডার নং</th>
            <th>তারিখ</th>
            <th>কাস্টমারের নাম ও ঠিকানা</th>
            <th>ফোন নম্বর</th>
            <th>ডেলিভারি এলাকা</th>
            <th>আইটেম</th>
            <th>মোট বিল</th>
            <th>স্ট্যাটাস</th>
            <th>অ্যাকশন</th>
          </tr>
        </thead>
        <tbody>
          @foreach($orders as $order)
          <tr>
            <td>
              <a href="{{ route('admin.orders.show', $order) }}" style="font-family: var(--font-en); font-weight: 700; color: var(--primary); text-decoration: none;">
                {{ $order->order_number }}
              </a>
            </td>
            <td>
              <span style="font-size: 0.82rem; color: #64748b;">{{ $order->created_at->format('d M Y') }}</span><br>
              <span style="font-size: 0.75rem; color: #94a3b8;">{{ $order->created_at->format('h:i A') }}</span>
            </td>
            <td>
              <strong>{{ $order->customer_name }}</strong><br>
              <span style="font-size: 0.8rem; color: #64748b; display: block; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $order->customer_address }}">
                {{ $order->customer_address }}
              </span>
            </td>
            <td>
              <a href="tel:{{ $order->customer_phone }}" style="font-size: 0.88rem; color: #2563eb; font-weight: 700; font-family: var(--font-en); text-decoration: none;">
                {{ $order->customer_phone }}
              </a>
            </td>
            <td>
              <span style="font-size: 0.82rem;">
                {{ $order->delivery_area === 'inside' ? 'ঢাকা সিটি' : 'ঢাকার বাইরে' }}
              </span>
            </td>
            <td>
              <span style="font-weight: 700; font-size: 0.88rem;">{{ $order->items->sum('quantity') }} টি</span>
            </td>
            <td>
              <strong style="font-family: var(--font-en); font-size: 1.05rem;">৳{{ number_format($order->grand_total) }}</strong>
            </td>
            <td>
              <span class="status-badge status-{{ $order->status }}">
                @switch($order->status)
                  @case('pending') পেন্ডিং @break
                  @case('confirmed') কনফার্মড @break
                  @case('processing') প্রসেসিং @break
                  @case('shipped') শিপড @break
                  @case('delivered') ডেলিভার্ড @break
                  @case('cancelled') বাতিল @break
                  @default {{ $order->status }}
                @endswitch
              </span>
            </td>
            <td>
              <div style="display: flex; gap: 6px;">
                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary btn-sm" title="বিস্তারিত দেখুন">
                  <i class="fa-solid fa-eye"></i>
                </a>
                <form action="{{ route('admin.orders.destroy', $order) }}" method="POST" data-confirm="আপনি কি নিশ্চিত এই অর্ডারটি মুছে ফেলতে চান?" style="margin:0;">
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

    <div style="margin-top: 20px;">
      {{ $orders->links() }}
    </div>
  @endif
</div>

@endsection
