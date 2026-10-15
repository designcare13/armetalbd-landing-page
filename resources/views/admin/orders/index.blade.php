@extends('admin.layout')

@section('title', 'অর্ডার তালিকা')
@section('page_title', 'অর্ডার ম্যানেজমেন্ট')

@section('content')

@php
  $statusLabels = [
    'pending' => 'পেন্ডিং',
    'confirmed' => 'কনফার্মড',
    'processing' => 'প্রসেসিং',
    'shipped' => 'শিপড',
    'delivered' => 'ডেলিভার্ড',
    'cancelled' => 'বাতিল',
  ];
  $keep = request()->only('per_page');
  $activeStatus = request('status');
@endphp

<style>
  .order-stats { grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 22px; }
  .order-stats .stat-card { padding: 16px 18px; gap: 14px; }
  .order-stats .stat-icon { width: 46px; height: 46px; font-size: 1.25rem; }
  .order-stats .stat-num { font-size: 1.4rem; }
  .order-stats .stat-label { font-size: 0.82rem; }
  @media (max-width: 1280px) { .order-stats { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 420px) { .order-stats { grid-template-columns: 1fr; } }

  .order-filters { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 20px; }
  .filter-pills { display: flex; gap: 8px; flex-wrap: wrap; }
  .filter-pill {
    display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 999px;
    font-size: 0.85rem; font-weight: 700; text-decoration: none; color: #475569;
    background: #f1f5f9; border: 1px solid transparent; transition: all 0.15s;
  }
  .filter-pill:hover { border-color: var(--primary); color: var(--primary); }
  .filter-pill.active { background: var(--primary); color: #fff; }
  .filter-pill .pill-count {
    min-width: 22px; padding: 1px 7px; border-radius: 999px; font-size: 0.74rem; text-align: center;
    background: #fff; color: #334155; font-family: var(--font-en);
  }
  .filter-pill.active .pill-count { background: rgba(255,255,255,0.22); color: #fff; }
  .order-search { display: flex; gap: 8px; }
  .order-search .form-control { width: 240px; padding: 8px 12px; font-size: 0.88rem; }

  .order-table th, .order-table td { padding-left: 12px; padding-right: 12px; }
  .order-table td { vertical-align: top; }
  .order-no { white-space: nowrap; font-family: var(--font-en); font-weight: 700; color: var(--primary); text-decoration: none; font-size: 0.95rem; }
  .order-no:hover { text-decoration: underline; }
  .muted { color: #64748b; font-size: 0.8rem; }
  .nowrap { white-space: nowrap; }
  .faint { color: #94a3b8; font-size: 0.75rem; }
  .customer { display: flex; gap: 10px; align-items: flex-start; min-width: 200px; }
  .avatar {
    width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
    background: var(--primary-light); color: var(--primary-dark); font-weight: 700; font-size: 1rem;
  }
  .customer-phone { color: #2563eb; font-weight: 700; font-size: 0.86rem; text-decoration: none; font-family: var(--font-en); }
  .customer-address { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .item-line { font-size: 0.86rem; font-weight: 600; max-width: 170px; }
  .area-chip { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 0.76rem; font-weight: 700; background: #f1f5f9; color: #475569; }
  .area-chip.inside { background: #ecfeff; color: #0e7490; }
  .area-chip.outside { background: #fff7ed; color: #c2410c; }
  .order-total { font-family: var(--font-en); font-size: 1.05rem; font-weight: 700; }
  .status-badge::before { content: ''; display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: currentColor; margin-right: 6px; }
  .row-actions { display: flex; gap: 6px; }

  @media (max-width: 760px) {
    .order-search { width: 100%; }
    .order-search .form-control { flex: 1; width: auto; }
    .filter-pills { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; width: 100%; }
    .filter-pill { flex-shrink: 0; }
    .order-table thead { display: none; }
    .order-table, .order-table tbody, .order-table tr, .order-table td { display: block; width: 100%; }
    .order-table tr { border: 1px solid var(--gray-light); border-radius: 12px; padding: 12px 14px; margin-bottom: 12px; background: #fff; }
    .order-table tr:hover td { background: transparent; }
    .order-table td { border: 0; padding: 6px 0; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; text-align: right; }
    .order-table td::before { content: attr(data-label); color: #94a3b8; font-size: 0.78rem; font-weight: 700; text-align: left; flex-shrink: 0; padding-top: 2px; }
    .order-table td.cell-customer { display: block; text-align: left; }
    .order-table td.cell-customer::before { display: block; margin-bottom: 6px; }
    .customer { min-width: 0; }
    .customer-address, .item-line { max-width: none; white-space: normal; }
    .order-table td.cell-actions { justify-content: flex-end; border-top: 1px solid var(--gray-light); margin-top: 6px; padding-top: 10px; }
    .order-table td.cell-actions::before { display: none; }
  }
</style>

<div class="stats-grid order-stats">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa-solid fa-receipt"></i></div>
    <div>
      <div class="stat-num">{{ number_format($summary['total']) }}</div>
      <div class="stat-label">মোট অর্ডার</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><i class="fa-solid fa-hourglass-half"></i></div>
    <div>
      <div class="stat-num">{{ number_format($summary['pending']) }}</div>
      <div class="stat-label">পেন্ডিং অর্ডার</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fa-solid fa-calendar-day"></i></div>
    <div>
      <div class="stat-num">{{ number_format($summary['today']) }}</div>
      <div class="stat-label">আজকের অর্ডার</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa-solid fa-sack-dollar"></i></div>
    <div>
      <div class="stat-num">৳{{ number_format($summary['delivered_value']) }}</div>
      <div class="stat-label">ডেলিভার্ড অর্ডারের বিক্রি</div>
    </div>
  </div>
</div>

<div class="card">
  <div class="order-filters">
    <div class="filter-pills">
      <a href="{{ route('admin.orders.index', $keep) }}" class="filter-pill {{ ! $activeStatus ? 'active' : '' }}">
        সকল অর্ডার <span class="pill-count">{{ $summary['total'] }}</span>
      </a>
      @foreach($statusLabels as $status => $label)
        <a href="{{ route('admin.orders.index', ['status' => $status] + $keep) }}" class="filter-pill {{ $activeStatus === $status ? 'active' : '' }}">
          {{ $label }} <span class="pill-count">{{ $statusCounts[$status] ?? 0 }}</span>
        </a>
      @endforeach
    </div>

    <form action="{{ route('admin.orders.index') }}" method="GET" class="order-search">
      @if($activeStatus)
        <input type="hidden" name="status" value="{{ $activeStatus }}">
      @endif
      @if(request('per_page'))
        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
      @endif
      <input type="text" name="search" class="form-control" placeholder="অর্ডার নং, নাম বা ফোন..." value="{{ request('search') }}">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-magnifying-glass"></i> খুঁজুন
      </button>
      @if(request('search'))
        <a href="{{ route('admin.orders.index', array_filter(['status' => $activeStatus] + $keep)) }}" class="btn btn-secondary btn-sm" title="ক্লিয়ার করুন">
          <i class="fa-solid fa-xmark"></i>
        </a>
      @endif
    </form>
  </div>

  @if($orders->isEmpty())
    <div style="text-align: center; padding: 40px; color: #64748b;">
      <i class="fa-solid fa-filter-circle-xmark" style="font-size: 2.5rem; margin-bottom: 12px; display: block; color: #cbd5e1;"></i>
      কোনো অর্ডার পাওয়া যায়নি।
    </div>
  @else
    <div class="table-responsive">
      <table class="table order-table">
        <thead>
          <tr>
            <th>অর্ডার</th>
            <th>কাস্টমার</th>
            <th>আইটেম</th>
            <th>মোট বিল</th>
            <th>স্ট্যাটাস</th>
            <th>অ্যাকশন</th>
          </tr>
        </thead>
        <tbody>
          @foreach($orders as $order)
          @php
            $firstItem = $order->items->first();
            $otherItems = max(0, $order->items->count() - 1);
          @endphp
          <tr>
            <td data-label="অর্ডার">
              <div>
                <a href="{{ route('admin.orders.show', $order) }}" class="order-no">{{ $order->order_number }}</a><br>
                <span class="muted nowrap">{{ $order->created_at->format('d M Y') }}</span><br>
                <span class="faint nowrap">{{ $order->created_at->format('h:i A') }}</span>
              </div>
            </td>
            <td class="cell-customer" data-label="কাস্টমার">
              <div class="customer">
                <div class="avatar">{{ mb_substr(trim($order->customer_name), 0, 1) }}</div>
                <div>
                  <strong>{{ $order->customer_name }}</strong><br>
                  <a href="tel:{{ $order->customer_phone }}" class="customer-phone"><i class="fa-solid fa-phone" style="font-size: 0.72rem;"></i> {{ $order->customer_phone }}</a>
                  <div class="muted customer-address" title="{{ $order->customer_address }}">{{ $order->customer_address }}</div>
                </div>
              </div>
            </td>
            <td data-label="আইটেম">
              <div>
                @if($firstItem)
                  <div class="item-line">{{ $firstItem->product_name }} <span class="muted">×{{ $firstItem->quantity }}</span></div>
                  @if($otherItems > 0)
                    <span class="faint">আরও {{ $otherItems }} টি প্রোডাক্ট</span>
                  @endif
                @else
                  <span class="faint">-</span>
                @endif
              </div>
            </td>
            <td data-label="মোট বিল ও এলাকা">
              <div style="text-align: inherit;">
                <span class="order-total">৳{{ number_format($order->grand_total) }}</span><br>
                <span class="faint">ডেলিভারি ৳{{ number_format($order->delivery_charge) }}</span><br>
                <span class="area-chip {{ $order->delivery_area }}" style="margin-top: 4px;">{{ $order->delivery_area === 'inside' ? 'ঢাকা সিটি' : 'ঢাকার বাইরে' }}</span>
              </div>
            </td>
            <td data-label="স্ট্যাটাস">
              <span class="status-badge status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
            </td>
            <td class="cell-actions" data-label="অ্যাকশন">
              <div class="row-actions">
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

    @include('admin._list-footer', ['paginator' => $orders])
  @endif
</div>

@endsection
