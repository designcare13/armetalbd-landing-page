@extends('admin.layout')

@section('title', 'ড্যাশবোর্ড - ওভারভিউ')
@section('page_title', 'ড্যাশবোর্ড ওভারভিউ')

@section('content')

<!-- KPI Metrics Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon green">
      <i class="fa-solid fa-bangladeshi-taka-sign"></i>
    </div>
    <div>
      <div class="stat-num">৳{{ number_format($totalRevenue) }}</div>
      <div class="stat-label">মোট বিক্রয় রেভিনিউ</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue">
      <i class="fa-solid fa-cart-shopping"></i>
    </div>
    <div>
      <div class="stat-num">{{ $totalOrders }}</div>
      <div class="stat-label">সর্বমোট অর্ডার</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon yellow">
      <i class="fa-solid fa-clock"></i>
    </div>
    <div>
      <div class="stat-num">{{ $pendingOrders }}</div>
      <div class="stat-label">পেন্ডিং / অপেক্ষমাণ অর্ডার</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon purple">
      <i class="fa-solid fa-calendar-day"></i>
    </div>
    <div>
      <div class="stat-num">{{ $todayOrders }}</div>
      <div class="stat-label">আজকের নতুন অর্ডার (৳{{ number_format($todayRevenue) }})</div>
    </div>
  </div>
</div>

<!-- Recent Orders Card -->
<div class="card">
  <div class="card-title">
    <span><i class="fa-solid fa-list-check"></i> সাম্প্রতিক অর্ডার সমূহ</span>
    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-sm">
      সব অর্ডার দেখুন <i class="fa-solid fa-arrow-right"></i>
    </a>
  </div>

  @if($recentOrders->isEmpty())
    <div style="text-align: center; padding: 40px; color: #64748b;">
      <i class="fa-solid fa-inbox" style="font-size: 2.5rem; margin-bottom: 12px; display: block; color: #cbd5e1;"></i>
      এখনো কোনো অর্ডার আসেনি। ল্যান্ডিং পেজে কাস্টমার অর্ডার করলে এখানে স্বয়ংক্রিয়ভাবে প্রদর্শিত হবে।
    </div>
  @else
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>অর্ডার নং</th>
            <th>তারিখ ও সময়</th>
            <th>কাস্টমারের নাম ও ফোন</th>
            <th>ডেলিভারি এরিয়া</th>
            <th>প্রোডাক্ট সমূহ</th>
            <th>মোট বিল</th>
            <th>স্ট্যাটাস</th>
            <th>অ্যাকশন</th>
          </tr>
        </thead>
        <tbody>
          @foreach($recentOrders as $order)
          <tr>
            <td>
              <strong style="font-family: var(--font-en); color: var(--primary);">{{ $order->order_number }}</strong>
            </td>
            <td>
              <span style="font-size: 0.82rem; color: #64748b;">{{ $order->created_at->inShopTime()->format('d M Y, h:i A') }}</span>
            </td>
            <td>
              <strong>{{ $order->customer_name }}</strong><br>
              <a href="tel:{{ $order->customer_phone }}" style="font-size: 0.85rem; color: #2563eb; font-family: var(--font-en);">
                <i class="fa-solid fa-phone"></i> {{ $order->customer_phone }}
              </a>
            </td>
            <td>
              <span style="font-size: 0.85rem;">
                {{ $order->delivery_area === 'inside' ? 'ঢাকা সিটির ভেতরে' : 'ঢাকার বাইরে' }}
              </span>
            </td>
            <td>
              <span style="font-size: 0.85rem; color: #475569;">
                {{ $order->items->map(fn($i) => "{$i->product_name} (×{$i->quantity})")->implode(', ') }}
              </span>
            </td>
            <td>
              <strong style="font-family: var(--font-en); font-size: 1.05rem; color: var(--dark);">
                ৳{{ number_format($order->grand_total) }}
              </strong>
            </td>
            <td>
              <span class="status-badge status-{{ $order->status }}">
                @switch($order->status)
                  @case('pending') পেন্ডিং @break
                  @case('confirmed') কনফার্মড @break
                  @case('processing') প্রসেসিং @break
                  @case('shipped') ডেলিভারিতে আছে @break
                  @case('delivered') সম্পন্ন @break
                  @case('cancelled') বাতিল @break
                  @default {{ $order->status }}
                @endswitch
              </span>
            </td>
            <td>
              <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary btn-sm" title="বিস্তারিত দেখুন">
                <i class="fa-solid fa-eye"></i> বিস্তারিত
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

@endsection
