<div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 16px;">
  <div class="form-group">
    <label class="form-label" for="name">গ্রাহকের নাম <span style="color:red;">*</span></label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $review->name ?? '') }}" maxlength="100" required>
  </div>

  <div class="form-group">
    <label class="form-label" for="location">এলাকা</label>
    <input type="text" name="location" id="location" class="form-control" value="{{ old('location', $review->location ?? '') }}" placeholder="যেমন: উত্তরা, ঢাকা" maxlength="100">
  </div>

  <div class="form-group">
    <label class="form-label" for="rating">রেটিং (১-৫)</label>
    <select name="rating" id="rating" class="form-control">
      @for($i = 5; $i >= 1; $i--)
        <option value="{{ $i }}" {{ (int) old('rating', $review->rating ?? 5) === $i ? 'selected' : '' }}>{{ $i }} স্টার</option>
      @endfor
    </select>
  </div>

  <div class="form-group">
    <label class="form-label" for="sort_order">ক্রম</label>
    <input type="number" name="sort_order" id="sort_order" class="form-control" value="{{ old('sort_order', $review->sort_order ?? 0) }}" min="0" max="9999">
  </div>
</div>

<div class="form-group">
  <label class="form-label" for="body">রিভিউ <span style="color:red;">*</span></label>
  <textarea name="body" id="body" rows="3" class="form-control" maxlength="1000" required>{{ old('body', $review->body ?? '') }}</textarea>
</div>

<label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 20px;">
  <input type="checkbox" name="is_active" value="1" {{ old('is_active', $review->is_active ?? true) ? 'checked' : '' }}>
  <strong>ওয়েবসাইটে দেখান</strong>
</label>
