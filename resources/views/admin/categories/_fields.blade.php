<div style="display: grid; grid-template-columns: 3fr 1fr; gap: 16px;">
  <div class="form-group">
    <label class="form-label" for="name">ক্যাটাগরির নাম <span style="color:red;">*</span></label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" placeholder="যেমন: সোফা ও চেয়ার" maxlength="100" required>
  </div>

  <div class="form-group">
    <label class="form-label" for="sort_order">ক্রম</label>
    <input type="number" name="sort_order" id="sort_order" class="form-control" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0" max="9999">
  </div>
</div>

<label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 20px;">
  <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
  <strong>ক্যাটাগরি সক্রিয় রাখুন (ওয়েবসাইটে দেখাবে)</strong>
</label>
