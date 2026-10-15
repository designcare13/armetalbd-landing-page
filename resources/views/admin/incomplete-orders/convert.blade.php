@extends('admin.layout')

@section('title', 'অর্ডারে রূপান্তর')
@section('page_title', 'ইনকমপ্লিট এন্ট্রি থেকে অর্ডার')

@section('content')

<div style="margin-bottom: 20px;">
  <a href="{{ route('admin.incomplete-orders.index') }}" class="btn btn-secondary btn-sm">
    <i class="fa-solid fa-arrow-left"></i> ইনকমপ্লিট অর্ডার তালিকায় ফিরে যান
  </a>
</div>

<p style="margin: 0 0 18px; color: #64748b; font-size: 0.9rem;">
  কাস্টমার ফোনে অর্ডার কনফার্ম করলে এখান থেকে আসল অর্ডার তৈরি করুন। অর্ডার তৈরি হলে স্টক কমে যাবে, অর্ডারটি "কনফার্মড" অবস্থায় শুরু হবে এবং এই এন্ট্রি তালিকা থেকে সরে যাবে।
</p>

@if($earlierOrders->isNotEmpty())
  <div style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; font-size: 0.9rem;">
    <strong><i class="fa-solid fa-triangle-exclamation"></i> এই নম্বরে আগেও অর্ডার আছে।</strong>
    একই অর্ডার দুইবার তৈরি হচ্ছে কিনা দেখে নিন:
    @foreach($earlierOrders as $earlierOrder)
      <a href="{{ route('admin.orders.show', $earlierOrder) }}" target="_blank" style="color: #92400e; font-weight: 700; font-family: var(--font-en);">{{ $earlierOrder->order_number }}</a>
      ({{ $earlierOrder->created_at->format('d M Y') }}, ৳{{ number_format($earlierOrder->grand_total) }}){{ $loop->last ? '' : ',' }}
    @endforeach
  </div>
@endif

<form action="{{ route('admin.incomplete-orders.convert.store', $incompleteOrder) }}" method="POST" id="convertForm">
  @csrf

  <div class="card" style="margin-bottom: 20px;">
    <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 18px;">কাস্টমারের তথ্য</h3>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
      <div class="form-group">
        <label class="form-label" for="customer_name">নাম <span style="color:red;">*</span></label>
        <input type="text" name="customer_name" id="customer_name" class="form-control" value="{{ old('customer_name', $incompleteOrder->customer_name) }}" maxlength="255" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="customer_phone">মোবাইল নম্বর <span style="color:red;">*</span></label>
        <input type="text" name="customer_phone" id="customer_phone" class="form-control" value="{{ old('customer_phone', $incompleteOrder->customer_phone) }}" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="customer_address">সম্পূর্ণ ঠিকানা <span style="color:red;">*</span></label>
      <textarea name="customer_address" id="customer_address" rows="3" class="form-control" maxlength="1000" required>{{ old('customer_address', $incompleteOrder->customer_address) }}</textarea>
    </div>

    <div class="form-group">
      <label class="form-label">ডেলিভারি এলাকা <span style="color:red;">*</span></label>
      @php($selectedArea = old('delivery_area', $incompleteOrder->delivery_area ?: 'inside'))
      <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
          <input type="radio" name="delivery_area" value="inside" data-charge="{{ $deliveryCharges['inside'] }}" @checked($selectedArea === 'inside')>
          ঢাকা সিটির ভেতরে (চার্জ ৳{{ number_format($deliveryCharges['inside']) }})
        </label>
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
          <input type="radio" name="delivery_area" value="outside" data-charge="{{ $deliveryCharges['outside'] }}" @checked($selectedArea === 'outside')>
          ঢাকার বাইরে (চার্জ ৳{{ number_format($deliveryCharges['outside']) }})
        </label>
      </div>
    </div>

    <div class="form-group" style="margin-bottom: 0;">
      <label class="form-label" for="order_note">বিশেষ নির্দেশনা (অপশনাল)</label>
      <input type="text" name="order_note" id="order_note" class="form-control" value="{{ old('order_note') }}" maxlength="500" placeholder="যেমন: কালারের পছন্দ বা ডেলিভারির সময়">
    </div>
  </div>

  <div class="card" style="margin-bottom: 20px;">
    <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 6px;">প্রোডাক্ট ও পরিমাণ</h3>
    <p style="margin: 0 0 16px; color: #64748b; font-size: 0.85rem;">দাম ও স্টক আজকের হিসাবে দেখানো হচ্ছে। কাস্টমার যা বলেছেন সেই অনুযায়ী পরিমাণ বদলে নিন। পরিমাণ ০ দিলে প্রোডাক্টটি অর্ডারে যাবে না।</p>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>প্রোডাক্ট</th>
            <th>দাম</th>
            <th>স্টক</th>
            <th style="width: 120px;">পরিমাণ</th>
          </tr>
        </thead>
        <tbody>
          @foreach($lines as $index => $line)
          <tr>
            <td>
              <strong>{{ $line['name'] }}</strong>
              @unless($line['available'])
                <br><span class="status-badge status-cancelled" style="margin-top: 4px;">{{ $line['reason'] }}</span>
              @endunless
            </td>
            <td style="font-family: var(--font-en);">৳{{ number_format($line['price']) }}</td>
            <td style="font-family: var(--font-en);">{{ $line['stock'] }}</td>
            <td>
              @if($line['available'])
                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $line['id'] }}">
                <input type="number" name="items[{{ $index }}][qty]" class="form-control" value="{{ old("items.$index.qty", $line['qty']) }}" min="0" max="{{ $line['max'] }}" data-price="{{ $line['price'] }}" style="padding: 6px 10px;">
              @else
                <span style="color: #94a3b8;">অর্ডার করা যাবে না</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if(collect($lines)->where('available', true)->isEmpty())
      <div style="margin-top: 14px; color: #b91c1c; font-weight: 600; font-size: 0.9rem;">
        <i class="fa-solid fa-circle-exclamation"></i> কার্টে এমন কোনো প্রোডাক্ট নেই যা এখন অর্ডার করা যায়। তাই এখান থেকে অর্ডার তৈরি করা যাচ্ছে না।
      </div>
    @endif

    <div style="margin-top: 20px; margin-left: auto; max-width: 320px; font-size: 0.95rem;">
      <div style="display: flex; justify-content: space-between; padding: 6px 0;"><span>সাবটোটাল</span><strong id="sumSubtotal" style="font-family: var(--font-en);">৳0</strong></div>
      <div style="display: flex; justify-content: space-between; padding: 6px 0;"><span>ডেলিভারি চার্জ</span><strong id="sumDelivery" style="font-family: var(--font-en);">৳0</strong></div>
      <div style="display: flex; justify-content: space-between; padding: 10px 0 0; border-top: 2px solid var(--gray-light); font-size: 1.1rem;"><span>মোট বিল (ক্যাশ অন ডেলিভারি)</span><strong id="sumTotal" style="font-family: var(--font-en); color: var(--primary);">৳0</strong></div>
    </div>
  </div>

  <div style="display: flex; gap: 10px;">
    <button type="submit" class="btn btn-primary" @disabled(collect($lines)->where('available', true)->isEmpty())>
      <i class="fa-solid fa-check"></i> অর্ডার তৈরি করুন
    </button>
    <a href="{{ route('admin.incomplete-orders.index') }}" class="btn btn-secondary">বাতিল</a>
  </div>
</form>

<script>
  (function () {
    var form = document.getElementById('convertForm');
    var money = function (amount) { return '৳' + Number(amount).toLocaleString('en-IN'); };

    function updateTotals() {
      var subtotal = 0;
      form.querySelectorAll('[data-price]').forEach(function (input) {
        subtotal += (parseInt(input.value, 10) || 0) * parseFloat(input.dataset.price);
      });
      var area = form.querySelector('input[name="delivery_area"]:checked');
      var delivery = subtotal > 0 && area ? parseFloat(area.dataset.charge) : 0;

      document.getElementById('sumSubtotal').textContent = money(subtotal);
      document.getElementById('sumDelivery').textContent = money(delivery);
      document.getElementById('sumTotal').textContent = money(subtotal + delivery);
    }

    form.addEventListener('input', updateTotals);
    form.addEventListener('change', updateTotals);
    updateTotals();
  })();
</script>

@endsection