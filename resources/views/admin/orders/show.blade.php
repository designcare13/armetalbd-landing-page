@extends('admin.layout')

@section('title', 'অর্ডার বিবরণী - ' . $order->order_number)
@section('page_title', 'অর্ডার ইনভয়েস ও বিবরণী')

@section('content')

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
  <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-sm">
    <i class="fa-solid fa-arrow-left"></i> অর্ডার তালিকায় ফিরে যান
  </a>

  <div style="display: flex; gap: 10px;">
    @if($order->isEditable())
      <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-pen-to-square"></i> অর্ডার এডিট করুন
      </a>
    @endif
    <button onclick="window.print()" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-print"></i> ইনভয়েস প্রিন্ট করুন
    </button>
  </div>
</div>

<div class="card" id="invoiceArea">
  <!-- Invoice Header -->
  <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid var(--gray-light); padding-bottom: 20px; margin-bottom: 24px;">
    <div>
      <img src="https://armetalbd.com/uploads/3_1769424079361_u87afs.png" alt="AR Metal Logo" style="height: 48px; margin-bottom: 8px;">
      <h2 style="font-size: 1.4rem; font-weight: 700;">AR METAL BD</h2>
      <p style="font-size: 0.85rem; color: #64748b;">মিরপুর, কালশী কবরস্থান, ঢাকা-১২১৬, বাংলাদেশ<br>ফোন: 01805962220 | ইমেইল: arif28391@gmail.com</p>
    </div>

    <div style="text-align: right;">
      <span style="font-size: 0.85rem; color: #64748b;">অর্ডার নম্বর</span>
      <h3 style="font-size: 1.6rem; font-weight: 700; color: var(--primary); font-family: var(--font-en); margin-bottom: 4px;">{{ $order->order_number }}</h3>
      <span style="font-size: 0.88rem; color: #64748b;">তারিখ: {{ $order->created_at->format('d M Y, h:i A') }}</span><br>
      <span style="font-size: 0.88rem; color: #64748b;">উৎস: {{ $order->sourceLabel() }}</span>
      <div style="margin-top: 8px;">
        <span class="status-badge status-{{ $order->status }}" style="font-size: 0.85rem; padding: 6px 16px;">
          স্ট্যাটাস: {{ strtoupper($order->status) }}
        </span>
      </div>
    </div>
  </div>

  <!-- Customer Details & Status Update Form -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 30px;">
    <!-- Customer Info -->
    <div style="background: var(--gray-soft); padding: 18px; border-radius: var(--radius); border: 1px solid var(--gray-light);">
      <h4 style="font-weight: 700; font-size: 1rem; margin-bottom: 12px; color: #334155;">
        <i class="fa-solid fa-user"></i> কাস্টমারের তথ্য
      </h4>
      <p style="font-size: 0.95rem; margin-bottom: 6px;"><strong>নাম:</strong> {{ $order->customer_name }}</p>
      <p style="font-size: 0.95rem; margin-bottom: 6px;">
        <strong>ফোন:</strong> 
        <a href="tel:{{ $order->customer_phone }}" style="color: #2563eb; font-weight: 700; font-family: var(--font-en);">
          {{ $order->customer_phone }}
        </a>
      </p>
      <p style="font-size: 0.95rem; margin-bottom: 12px;"><strong>ঠিকানা:</strong> {{ $order->customer_address }}</p>
      <p style="font-size: 0.95rem; margin-bottom: 14px;">
        <strong>ডেলিভারি এরিয়া:</strong> 
        {{ $order->delivery_area === 'inside' ? 'ঢাকা সিটির ভেতরে (৳' . $order->delivery_charge . ')' : 'ঢাকার বাইরে (৳' . $order->delivery_charge . ')' }}
      </p>

      <!-- Quick Action Buttons -->
      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="tel:{{ $order->customer_phone }}" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-phone"></i> কল দিন
        </a>
        <a href="https://api.whatsapp.com/send?phone=88{{ preg_replace('/^0/', '', $order->customer_phone) }}&text={{ urlencode('আসসালামু আলাইকুম ' . $order->customer_name . ', AR Metal BD থেকে আপনার অর্ডার ' . $order->order_number . ' নিশ্চিতকরণ সংক্রান্ত যোগাযোগ করছি।') }}" target="_blank" class="btn btn-sm" style="background: #25d366; color: white;">
          <i class="fa-brands fa-whatsapp"></i> হোয়াটসঅ্যাপ
        </a>
      </div>
    </div>

    <!-- Status Change Form -->
    <div style="background: var(--gray-soft); padding: 18px; border-radius: var(--radius); border: 1px solid var(--gray-light);">
      <h4 style="font-weight: 700; font-size: 1rem; margin-bottom: 12px; color: #334155;">
        <i class="fa-solid fa-gear"></i> অর্ডারের স্ট্যাটাস পরিবর্তন
      </h4>
      <form action="{{ route('admin.orders.status', $order) }}" method="POST">
        @csrf
        @method('PATCH')
        <div class="form-group">
          <label class="form-label">বর্তমান অবস্থা পরিবর্তন করুন:</label>
          <select name="status" class="form-control" style="background: white;">
            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>পেন্ডিং (Pending)</option>
            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>কনফার্মড (Confirmed)</option>
            <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>প্রসেসিং হচ্ছে (Processing)</option>
            <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>কুরিয়ারে পাঠানো হয়েছে (Shipped)</option>
            <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>ডেলিভারি সম্পন্ন (Delivered)</option>
            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>অর্ডার বাতিল (Cancelled)</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-floppy-disk"></i> স্ট্যাটাস সেভ করুন
        </button>
      </form>

      @if($order->order_note)
        <div style="margin-top: 16px; border-top: 1px dashed #cbd5e1; padding-top: 12px;">
          <strong style="font-size: 0.88rem; color: #64748b;">কাস্টমারের বিশেষ নোট:</strong>
          <p style="font-size: 0.9rem; color: #1e293b; margin-top: 4px;">{{ $order->order_note }}</p>
        </div>
      @endif
    </div>
  </div>

  <!-- Ordered Products Table -->
  <h4 style="font-weight: 700; font-size: 1.1rem; margin-bottom: 14px;">
    <i class="fa-solid fa-boxes-stacked"></i> অর্ডারকৃত আইটেম তালিকা
  </h4>
  <div class="table-responsive" style="margin-bottom: 24px;">
    <table class="table">
      <thead>
        <tr>
          <th>ছবি</th>
          <th>প্রোডাক্টের নাম</th>
          <th>একক মূল্য</th>
          <th>পরিমাণ</th>
          <th style="text-align: right;">মোট টাকা</th>
        </tr>
      </thead>
      <tbody>
        @foreach($order->items as $item)
        <tr>
          <td style="width: 70px;">
            <img src="{{ $item->product_image ?? 'https://armetalbd.com/preset/fixing1.png' }}" alt="{{ $item->product_name }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
          </td>
          <td>
            <strong>{{ $item->product_name }}</strong>
          </td>
          <td style="font-family: var(--font-en); font-weight: 600;">
            ৳{{ number_format($item->unit_price) }}
          </td>
          <td style="font-weight: 700;">
            {{ $item->quantity }} টি
          </td>
          <td style="text-align: right; font-family: var(--font-en); font-weight: 700; font-size: 1.05rem;">
            ৳{{ number_format($item->line_total) }}
          </td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" style="text-align: right; font-weight: 700;">প্রোডাক্ট সাব-টোটাল:</td>
          <td style="text-align: right; font-family: var(--font-en); font-weight: 700;">৳{{ number_format($order->subtotal) }}</td>
        </tr>
        <tr>
          <td colspan="4" style="text-align: right; font-weight: 700;">ডেলিভারি চার্জ:</td>
          <td style="text-align: right; font-family: var(--font-en); font-weight: 700;">৳{{ number_format($order->delivery_charge) }}</td>
        </tr>
        <tr style="background: #f8fafc;">
          <td colspan="4" style="text-align: right; font-weight: 700; font-size: 1.15rem; color: var(--primary);">সর্বমোট প্রদেয় বিল (COD):</td>
          <td style="text-align: right; font-family: var(--font-en); font-weight: 700; font-size: 1.25rem; color: var(--primary);">
            ৳{{ number_format($order->grand_total) }}
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

@endsection
