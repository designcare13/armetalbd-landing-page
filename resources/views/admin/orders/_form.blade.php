{{-- Shared by the "new order" and "edit order" pages. Needs $order (null when new), $products, $deliveryCharges, $startingLines, $action and $method. --}}
@php
  $isNew = $order === null;
  $oldItems = old('items');
  $lines = is_array($oldItems)
      ? array_values(array_map(fn ($item) => ['id' => (int) ($item['id'] ?? 0), 'qty' => (int) ($item['qty'] ?? 1), 'price' => (float) ($item['price'] ?? 0)], $oldItems))
      : $startingLines;
  $selectedArea = old('delivery_area', $order->delivery_area ?? 'inside');
  $deliveryCharge = old('delivery_charge', $order ? (float) $order->delivery_charge : $deliveryCharges[$selectedArea]);
  $selectedSource = old('source', $order->source ?? 'phone');
  $formConfig = ['products' => $products, 'lines' => $lines, 'charges' => $deliveryCharges];
@endphp

<style>
  .order-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .line-table input.form-control, .line-table select.form-control { padding: 8px 10px; font-size: 0.9rem; }
  .line-table td { vertical-align: top; }
  .line-table .line-qty { min-width: 66px; }
  .line-table .line-price { min-width: 108px; }
  .line-stock { font-size: 0.75rem; color: #64748b; margin-top: 3px; }
  .line-stock.short { color: #b91c1c; font-weight: 700; }
  .line-total { font-family: var(--font-en); font-weight: 700; white-space: nowrap; padding-top: 18px; }
  .sum-box { margin: 20px 0 0 auto; max-width: 340px; font-size: 0.95rem; }
  .sum-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; gap: 12px; }
  .sum-row.total { border-top: 2px solid var(--gray-light); margin-top: 6px; padding-top: 12px; font-size: 1.1rem; }
  @media (max-width: 700px) { .order-form-grid { grid-template-columns: 1fr; } }
</style>

<form action="{{ $action }}" method="POST" id="orderForm">
  @csrf
  @if($method !== 'POST') @method($method) @endif

  <div class="card" style="margin-bottom: 20px;">
    <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 18px;">কাস্টমারের তথ্য</h3>

    <div class="order-form-grid">
      <div class="form-group">
        <label class="form-label" for="customer_name">নাম <span style="color:red;">*</span></label>
        <input type="text" name="customer_name" id="customer_name" class="form-control" value="{{ old('customer_name', $order->customer_name ?? '') }}" maxlength="255" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="customer_phone">মোবাইল নম্বর <span style="color:red;">*</span></label>
        <input type="text" name="customer_phone" id="customer_phone" class="form-control" value="{{ old('customer_phone', $order->customer_phone ?? '') }}" placeholder="01XXXXXXXXX" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="customer_address">সম্পূর্ণ ঠিকানা <span style="color:red;">*</span></label>
      <textarea name="customer_address" id="customer_address" rows="3" class="form-control" maxlength="1000" required>{{ old('customer_address', $order->customer_address ?? '') }}</textarea>
    </div>

    <div class="form-group">
      <label class="form-label">ডেলিভারি এলাকা <span style="color:red;">*</span></label>
      <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
          <input type="radio" name="delivery_area" value="inside" data-charge="{{ $deliveryCharges['inside'] }}" @checked($selectedArea === 'inside')>
          ঢাকা সিটির ভেতরে (সাধারণ চার্জ ৳{{ number_format($deliveryCharges['inside']) }})
        </label>
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
          <input type="radio" name="delivery_area" value="outside" data-charge="{{ $deliveryCharges['outside'] }}" @checked($selectedArea === 'outside')>
          ঢাকার বাইরে (সাধারণ চার্জ ৳{{ number_format($deliveryCharges['outside']) }})
        </label>
      </div>
    </div>

    <div class="form-group" style="margin-bottom: 0;">
      <label class="form-label" for="order_note">বিশেষ নির্দেশনা (অপশনাল)</label>
      <input type="text" name="order_note" id="order_note" class="form-control" value="{{ old('order_note', $order->order_note ?? '') }}" maxlength="500" placeholder="যেমন: কালারের পছন্দ বা ডেলিভারির সময়">
    </div>
  </div>

  <div class="card" style="margin-bottom: 20px;">
    <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 18px;">অর্ডারের উৎস{{ $isNew ? ' ও অবস্থা' : '' }}</h3>

    <div class="order-form-grid">
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" for="source">অর্ডার কোথা থেকে এসেছে <span style="color:red;">*</span></label>
        <select name="source" id="source" class="form-control">
          @foreach(\App\Models\Order::SOURCES as $key => $label)
            <option value="{{ $key }}" @selected($selectedSource === $key)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      @if($isNew)
        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" for="status">শুরুর অবস্থা</label>
          <select name="status" id="status" class="form-control">
            <option value="confirmed" @selected(old('status', 'confirmed') === 'confirmed')>কনফার্মড (কাস্টমার নিশ্চিত করেছেন)</option>
            <option value="pending" @selected(old('status') === 'pending')>পেন্ডিং</option>
          </select>
        </div>
      @endif
    </div>

    @if($isNew)
      <label style="display: flex; align-items: flex-start; gap: 8px; margin-top: 16px; cursor: pointer; font-size: 0.9rem;">
        <input type="checkbox" name="send_to_meta" value="1" style="margin-top: 4px;" @checked(old('send_to_meta', $errors->any() ? false : true))>
        <span><strong>ফেসবুকে কনভার্সন পাঠান</strong><br><span style="color: #64748b;">বিজ্ঞাপন থেকে আসা ফোন অর্ডার হলে টিক রাখুন, এতে Meta বুঝবে কোন বিজ্ঞাপনে বিক্রি হয়েছে। শোরুম বা পুরনো কাস্টমারের অর্ডার হলে টিক তুলে দিন।</span></span>
      </label>
    @endif
  </div>

  <div class="card" style="margin-bottom: 20px;">
    <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 6px;">প্রোডাক্ট, পরিমাণ ও দাম</h3>
    <p style="margin: 0 0 16px; color: #64748b; font-size: 0.85rem;">
      @if($isNew)
        প্রোডাক্ট বেছে নিলে দাম নিজে থেকে বসবে। কাস্টমারের সাথে দর-কষাকষি হলে দামের ঘর বদলে নিতে পারেন। অর্ডার তৈরি হলে স্টক কমে যাবে।
      @else
        আগের প্রোডাক্টগুলোর স্টক ফেরত যাবে এবং নতুন তালিকা অনুযায়ী আবার কমবে। পরিমাণ ০ দিলে বা ✕ চাপলে প্রোডাক্টটি অর্ডার থেকে বাদ যাবে।
      @endif
    </p>

    <div class="table-responsive">
      <table class="table line-table">
        <thead>
          <tr>
            <th style="min-width: 200px;">প্রোডাক্ট</th>
            <th style="width: 90px;">পরিমাণ</th>
            <th style="width: 130px;">একক দাম (৳)</th>
            <th style="width: 100px;">মোট</th>
            <th style="width: 46px;"></th>
          </tr>
        </thead>
        <tbody id="lineRows"></tbody>
      </table>
    </div>

    <button type="button" class="btn btn-secondary btn-sm" id="addLine" style="margin-top: 12px;">
      <i class="fa-solid fa-plus"></i> আরেকটি প্রোডাক্ট যোগ করুন
    </button>

    <div class="sum-box">
      <div class="sum-row"><span>সাবটোটাল</span><strong id="sumSubtotal" style="font-family: var(--font-en);">৳0</strong></div>
      <div class="sum-row">
        <label for="delivery_charge">ডেলিভারি চার্জ (৳)</label>
        <input type="number" step="0.01" min="0" max="99999" name="delivery_charge" id="delivery_charge" class="form-control" value="{{ $deliveryCharge }}" style="width: 130px; padding: 6px 10px;" required>
      </div>
      <div class="sum-row total"><span>মোট বিল (ক্যাশ অন ডেলিভারি)</span><strong id="sumTotal" style="font-family: var(--font-en); color: var(--primary);">৳0</strong></div>
    </div>
  </div>

  <div style="display: flex; gap: 10px; flex-wrap: wrap;">
    <button type="submit" class="btn btn-primary" id="saveOrder">
      <i class="fa-solid fa-check"></i> {{ $isNew ? 'অর্ডার তৈরি করুন' : 'পরিবর্তন সংরক্ষণ করুন' }}
    </button>
    <a href="{{ $isNew ? route('admin.orders.index') : route('admin.orders.show', $order) }}" class="btn btn-secondary">বাতিল</a>
  </div>
</form>

<script>
  window.ORDER_FORM = @json($formConfig);

  (function () {
    var config = window.ORDER_FORM;
    var form = document.getElementById('orderForm');
    var rows = document.getElementById('lineRows');
    var charge = document.getElementById('delivery_charge');
    var nextIndex = 0;

    var money = function (amount) { return '৳' + Number(amount).toLocaleString('en-IN'); };
    var productById = function (id) { return config.products.find(function (product) { return product.id === Number(id); }); };

    function field(tag, attributes) {
      var element = document.createElement(tag);
      Object.keys(attributes || {}).forEach(function (name) { element.setAttribute(name, attributes[name]); });
      return element;
    }

    function addRow(line) {
      var index = nextIndex++;
      var row = field('tr');

      var select = field('select', { name: 'items[' + index + '][id]', class: 'form-control line-product', required: 'required' });
      var blank = field('option', { value: '' });
      blank.textContent = 'প্রোডাক্ট বাছাই করুন';
      select.appendChild(blank);
      config.products.forEach(function (product) {
        var option = field('option', { value: product.id });
        option.textContent = product.name + ' — ৳' + product.price + ' (স্টক ' + product.stock + ')';
        if (line && Number(line.id) === product.id) { option.selected = true; }
        select.appendChild(option);
      });

      var qty = field('input', { type: 'number', name: 'items[' + index + '][qty]', class: 'form-control line-qty', min: '0', step: '1', value: line ? line.qty : 1 });
      var price = field('input', { type: 'number', name: 'items[' + index + '][price]', class: 'form-control line-price', min: '0', step: '0.01', required: 'required', value: line ? line.price : '' });
      var stock = field('div', { class: 'line-stock' });
      var total = field('td', { class: 'line-total' });
      var remove = field('button', { type: 'button', class: 'btn btn-danger btn-sm', title: 'বাদ দিন' });
      remove.textContent = '✕';

      var productCell = field('td');
      productCell.appendChild(select);
      productCell.appendChild(stock);
      var qtyCell = field('td');
      qtyCell.appendChild(qty);
      var priceCell = field('td');
      priceCell.appendChild(price);
      var removeCell = field('td');
      removeCell.appendChild(remove);
      [productCell, qtyCell, priceCell, total, removeCell].forEach(function (cell) { row.appendChild(cell); });

      select.addEventListener('change', function () {
        var product = productById(select.value);
        if (product) { price.value = product.price; if (!Number(qty.value)) { qty.value = 1; } }
        refresh();
      });
      qty.addEventListener('input', refresh);
      price.addEventListener('input', refresh);
      remove.addEventListener('click', function () { row.remove(); refresh(); });

      rows.appendChild(row);
      refresh();
    }

    function refresh() {
      var subtotal = 0;

      rows.querySelectorAll('tr').forEach(function (row) {
        var product = productById(row.querySelector('.line-product').value);
        var qty = parseInt(row.querySelector('.line-qty').value, 10) || 0;
        var price = parseFloat(row.querySelector('.line-price').value) || 0;
        var stock = row.querySelector('.line-stock');

        row.querySelector('.line-total').textContent = money(qty * price);
        subtotal += qty * price;

        if (product) {
          var short = qty > product.stock;
          stock.textContent = 'স্টকে পাওয়া যাবে: ' + product.stock + ' টি' + (short ? ' — এর বেশি দেওয়া যাবে না' : '');
          stock.classList.toggle('short', short);
          row.querySelector('.line-qty').max = product.stock;
        } else {
          stock.textContent = '';
        }
      });

      document.getElementById('sumSubtotal').textContent = money(subtotal);
      document.getElementById('sumTotal').textContent = money(subtotal + (parseFloat(charge.value) || 0));
    }

    form.querySelectorAll('input[name="delivery_area"]').forEach(function (radio) {
      radio.addEventListener('change', function () { charge.value = config.charges[radio.value]; refresh(); });
    });
    charge.addEventListener('input', refresh);
    document.getElementById('addLine').addEventListener('click', function () { addRow(null); });
    form.addEventListener('submit', function () { document.getElementById('saveOrder').disabled = true; });

    (config.lines.length ? config.lines : [null]).forEach(addRow);
  })();
</script>
