@php $currentImage = isset($product) ? $product->image_url : null; @endphp

<div class="form-group">
  <label class="form-label" for="image">
    প্রোডাক্টের ছবি @if(empty($product))<span style="color:red;">*</span>@endif
  </label>

  <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
    @if($currentImage)
      <img src="{{ $currentImage }}" alt="বর্তমান ছবি" id="imagePreview"
           style="width: 96px; height: 96px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0;">
    @else
      <img src="" alt="প্রিভিউ" id="imagePreview" style="display: none; width: 96px; height: 96px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0;">
    @endif

    <div>
      <input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/webp"
             {{ empty($product) ? 'required' : '' }} onchange="previewProductImage(this)">
      <small style="color: #64748b; display: block; margin-top: 4px;">
        JPG, PNG বা WEBP • সর্বোচ্চ ১০ MB • কমপক্ষে ৩০০×৩০০ পিক্সেল • ৮০০×৮০০ বা বড় হলে জুম পরিষ্কার দেখায়
        @if(!empty($product)) • নতুন ছবি দিলে পুরনো ছবি স্বয়ংক্রিয়ভাবে মুছে যাবে @endif
      </small>
      <small style="color: #64748b; display: block; margin-top: 2px;">
        মোবাইলের তোলা বড় ছবি সরাসরি দিতে পারেন, সেভ হওয়ার সময় নিজে থেকে ছোট করে নেওয়া হবে।
      </small>
      <div id="imageQualityNote" role="status" style="display: none; margin-top: 8px; max-width: 520px; background: #fef3c7; color: #92400e; border: 1px solid #fde68a; border-radius: 8px; padding: 8px 12px; font-size: 0.85rem; line-height: 1.5;"></div>
    </div>
  </div>
</div>

<script>
  var MIN_IMAGE_SIZE = {{ \App\Http\Controllers\Admin\ProductController::MIN_IMAGE_SIZE }};
  var GOOD_IMAGE_SIZE = {{ \App\Http\Controllers\Admin\ProductController::GOOD_IMAGE_SIZE }};
  var MAX_UPLOAD_MB = {{ \App\Http\Controllers\Admin\ProductController::MAX_UPLOAD_KB / 1024 }};

  function toBangla(value) {
    return String(value).replace(/\d/g, function (d) { return '০১২৩৪৫৬৭৮৯'[d]; });
  }

  function previewProductImage(input) {
    var note = document.getElementById('imageQualityNote');
    note.style.display = 'none';

    var file = input.files && input.files[0];
    if (!file) { return; }
    if (['image/jpeg', 'image/png', 'image/webp'].indexOf(file.type) === -1) {
      alert('শুধু JPG, PNG বা WEBP ছবি দিন।'); input.value = ''; return;
    }
    if (file.size > MAX_UPLOAD_MB * 1024 * 1024) {
      alert('ছবির সাইজ ' + toBangla(MAX_UPLOAD_MB) + ' MB এর বেশি হতে পারবে না।'); input.value = ''; return;
    }

    var preview = document.getElementById('imagePreview');
    var url = URL.createObjectURL(file);
    var probe = new Image();
    probe.onload = function () {
      var size = toBangla(probe.naturalWidth) + '×' + toBangla(probe.naturalHeight);

      if (probe.naturalWidth < MIN_IMAGE_SIZE || probe.naturalHeight < MIN_IMAGE_SIZE) {
        alert('ছবিটির মাপ ' + size + ' পিক্সেল, যা অনেক ছোট। কমপক্ষে '
          + toBangla(MIN_IMAGE_SIZE) + '×' + toBangla(MIN_IMAGE_SIZE) + ' পিক্সেলের ছবি দিন।');
        input.value = '';
        URL.revokeObjectURL(url);
        return;
      }

      if (probe.naturalWidth < GOOD_IMAGE_SIZE || probe.naturalHeight < GOOD_IMAGE_SIZE) {
        note.textContent = 'ছবিটির মাপ ' + size + ' পিক্সেল, একটু ছোট। বড় পপআপে জুম করলে ঝাপসা দেখাতে পারে। '
          + 'ভালো ও বড় ছবি থাকলে সেটা দিন, না থাকলে এটাই ব্যবহার করতে পারেন।';
        note.style.display = 'block';
      }

      preview.src = url;
      preview.style.display = 'block';
    };
    probe.src = url;
  }
</script>