@extends('admin.layout')

@section('title', 'ইনকমপ্লিট অর্ডার')
@section('page_title', 'ইনকমপ্লিট অর্ডার')

@section('content')

<div class="card">
  <p style="margin: 0 0 18px; color: #64748b; font-size: 0.9rem;">
    যাঁরা ফর্মে ফোন নম্বর দিয়েছেন কিন্তু অর্ডার সম্পন্ন করেননি, তাঁদের তথ্য এখানে দেখাচ্ছে। ফোন করে অর্ডারটা নিয়ে নিন। তথ্য {{ strtr((string) \App\Models\IncompleteOrder::RETENTION_DAYS, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']) }} দিন পর নিজে থেকে মুছে যায়, আর অর্ডার সম্পন্ন হলে সাথে সাথে তালিকা থেকে সরে যায়।
  </p>

  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <!-- Status filter pills -->
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
      <a href="{{ route('admin.incomplete-orders.index') }}" class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-secondary' }}">
        সবগুলো ({{ $statusCounts->sum() }})
      </a>
      @foreach(\App\Models\IncompleteOrder::STATUS_LABELS as $statusKey => $statusLabel)
        <a href="{{ route('admin.incomplete-orders.index', ['status' => $statusKey]) }}" class="btn btn-sm {{ request('status') === $statusKey ? 'btn-primary' : 'btn-secondary' }}">
          {{ $statusLabel }} ({{ $statusCounts[$statusKey] ?? 0 }})
        </a>
      @endforeach
    </div>

    <!-- Search -->
    <form action="{{ route('admin.incomplete-orders.index') }}" method="GET" style="display: flex; gap: 8px;">
      @if(request('status'))
        <input type="hidden" name="status" value="{{ request('status') }}">
      @endif
      <input type="text" name="search" class="form-control" placeholder="নাম বা ফোন..." value="{{ request('search') }}" style="width: 200px; padding: 6px 12px; font-size: 0.88rem;">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-magnifying-glass"></i> খুঁজুন
      </button>
      @if(request('search'))
        <a href="{{ route('admin.incomplete-orders.index', request('status') ? ['status' => request('status')] : []) }}" class="btn btn-secondary btn-sm" title="ক্লিয়ার করুন">
          <i class="fa-solid fa-xmark"></i>
        </a>
      @endif
    </form>
  </div>

  @if($incompleteOrders->isEmpty())
    <div style="text-align: center; padding: 40px; color: #64748b;">
      <i class="fa-solid fa-hourglass-half" style="font-size: 2.5rem; margin-bottom: 12px; display: block; color: #cbd5e1;"></i>
      কোনো ইনকমপ্লিট অর্ডার নেই।
    </div>
  @else
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>সর্বশেষ আপডেট</th>
            <th>কাস্টমারের নাম ও ঠিকানা</th>
            <th>ফোন নম্বর</th>
            <th>কার্টের প্রোডাক্ট</th>
            <th>মোট</th>
            <th>স্ট্যাটাস</th>
            <th>অ্যাকশন</th>
          </tr>
        </thead>
        <tbody>
          @foreach($incompleteOrders as $incompleteOrder)
          <tr>
            <td>
              <span style="font-size: 0.82rem; color: #64748b;">{{ $incompleteOrder->last_captured_at->inShopTime()->format('d M Y') }}</span><br>
              <span style="font-size: 0.75rem; color: #94a3b8;">{{ $incompleteOrder->last_captured_at->inShopTime()->format('h:i A') }}</span>
            </td>
            <td>
              @if($incompleteOrder->customer_name)
                <strong>{{ $incompleteOrder->customer_name }}</strong><br>
              @else
                <span style="color: #94a3b8;">নাম দেননি</span><br>
              @endif
              <span style="font-size: 0.8rem; color: #64748b; display: block; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $incompleteOrder->customer_address }}">
                {{ $incompleteOrder->customer_address ?: 'ঠিকানা দেননি' }}
              </span>
            </td>
            <td>
              <a href="tel:{{ $incompleteOrder->customer_phone }}" style="font-size: 0.88rem; color: #2563eb; font-weight: 700; font-family: var(--font-en); text-decoration: none;">
                <i class="fa-solid fa-phone" style="font-size: 0.75rem;"></i> {{ $incompleteOrder->customer_phone }}
              </a>
              <br>
              <a href="https://wa.me/88{{ $incompleteOrder->customer_phone }}" target="_blank" rel="noopener" style="font-size: 0.8rem; color: #15803d; font-weight: 600; text-decoration: none;">
                <i class="fa-brands fa-whatsapp"></i> WhatsApp
              </a>
            </td>
            <td>
              @forelse($incompleteOrder->items ?? [] as $item)
                <div style="font-size: 0.82rem; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $item['name'] }}">
                  {{ $item['name'] }} <strong>× {{ $item['qty'] }}</strong>
                </div>
              @empty
                <span style="color: #94a3b8; font-size: 0.82rem;">কার্ট খালি</span>
              @endforelse
            </td>
            <td>
              <strong style="font-family: var(--font-en);">৳{{ number_format($incompleteOrder->cart_total) }}</strong>
            </td>
            <td>
              <form action="{{ route('admin.incomplete-orders.status', $incompleteOrder) }}" method="POST" style="margin: 0;">
                @csrf
                @method('PATCH')
                <select name="status" class="form-control" onchange="this.form.submit()" style="padding: 5px 8px; font-size: 0.82rem; min-width: 120px;" aria-label="স্ট্যাটাস">
                  @foreach(\App\Models\IncompleteOrder::STATUS_LABELS as $statusKey => $statusLabel)
                    <option value="{{ $statusKey }}" @selected($incompleteOrder->status === $statusKey)>{{ $statusLabel }}</option>
                  @endforeach
                </select>
              </form>
            </td>
            <td>
              <div style="display: flex; gap: 6px;">
                <a href="{{ route('admin.incomplete-orders.convert', $incompleteOrder) }}" class="btn btn-primary btn-sm" title="কাস্টমার ফোনে কনফার্ম করলে আসল অর্ডার তৈরি করুন">
                  <i class="fa-solid fa-bag-shopping"></i> অর্ডার করুন
                </a>
                <form action="{{ route('admin.incomplete-orders.destroy', $incompleteOrder) }}" method="POST" data-confirm="আপনি কি নিশ্চিত এই এন্ট্রিটি মুছে ফেলতে চান?" style="margin: 0;">
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
      {{ $incompleteOrders->links() }}
    </div>
  @endif
</div>

@endsection