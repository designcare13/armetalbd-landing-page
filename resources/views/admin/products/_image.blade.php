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
        JPG, PNG বা WEBP • সর্বোচ্চ ২ MB • কমপক্ষে ৮০০×৮০০ পিক্সেল (জুম পরিষ্কার দেখাতে ১০০০×১০০০ বা বড় ছবি দিন)
        @if(!empty($product)) • নতুন ছবি দিলে পুরনো ছবি স্বয়ংক্রিয়ভাবে মুছে যাবে @endif
      </small>
    </div>
  </div>
</div>

<script>
  var MIN_IMAGE_SIZE = {{ \App\Http\Controllers\Admin\ProductController::MIN_IMAGE_SIZE }};

  function toBangla(value) {
    return String(value).replace(/\d/g, function (d) { return '০১২৩৪৫৬৭৮৯'[d]; });
  }

  function previewProductImage(input) {
    var file = input.files && input.files[0];
    if (!file) { return; }
    if (['image/jpeg', 'image/png', 'image/webp'].indexOf(file.type) === -1) {
      alert('শুধু JPG, PNG বা WEBP ছবি দিন।'); input.value = ''; return;
    }
    if (file.size > 2 * 1024 * 1024) {
      alert('ছবির সাইজ ২ MB এর বেশি হতে পারবে না।'); input.value = ''; return;
    }
    var preview = document.getElementById('imagePreview');
    var url = URL.createObjectURL(file);
    var probe = new Image();
    probe.onload = function () {
      if (probe.naturalWidth < MIN_IMAGE_SIZE || probe.naturalHeight < MIN_IMAGE_SIZE) {
        alert('ছবিটির মাপ ' + toBangla(probe.naturalWidth) + '×' + toBangla(probe.naturalHeight) + ' পিক্সেল। কমপক্ষে '
          + toBangla(MIN_IMAGE_SIZE) + '×' + toBangla(MIN_IMAGE_SIZE) + ' পিক্সেলের ছবি দিন, নইলে জুম করলে ঝাপসা দেখাবে।');
        input.value = '';
        URL.revokeObjectURL(url);
        return;
      }
      preview.src = url;
      preview.style.display = 'block';
    };
    probe.src = url;
  }
</script>
