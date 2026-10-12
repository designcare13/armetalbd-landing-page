<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
  <div class="form-group">
    <label class="form-label" for="stock">স্টক (পিস) <span style="color:red;">*</span></label>
    <input type="number" name="stock" id="stock" class="form-control" value="{{ old('stock', $product->stock ?? 100) }}" min="0" max="1000000" required>
    <small style="color: #64748b;">অর্ডার হলে স্টক কমবে, ০ হলে ওয়েবসাইটে "স্টক শেষ" দেখাবে।</small>
  </div>

  <div class="form-group">
    <label class="form-label" for="rating">রেটিং (০-৫) <span style="color:red;">*</span></label>
    <input type="number" step="0.1" name="rating" id="rating" class="form-control" value="{{ old('rating', $product->rating ?? 5) }}" min="0" max="5" required>
  </div>

  <div class="form-group">
    <label class="form-label" for="reviews_count">রিভিউ সংখ্যা <span style="color:red;">*</span></label>
    <input type="number" name="reviews_count" id="reviews_count" class="form-control" value="{{ old('reviews_count', $product->reviews_count ?? 0) }}" min="0" max="1000000" required>
  </div>
</div>
