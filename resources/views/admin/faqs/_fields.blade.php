<div style="display: grid; grid-template-columns: 4fr 1fr; gap: 16px;">
  <div class="form-group">
    <label class="form-label" for="question">প্রশ্ন <span style="color:red;">*</span></label>
    <input type="text" name="question" id="question" class="form-control" value="{{ old('question', $faq->question ?? '') }}" maxlength="255" required>
  </div>

  <div class="form-group">
    <label class="form-label" for="sort_order">ক্রম</label>
    <input type="number" name="sort_order" id="sort_order" class="form-control" value="{{ old('sort_order', $faq->sort_order ?? 0) }}" min="0" max="9999">
  </div>
</div>

<div class="form-group">
  <label class="form-label" for="answer">উত্তর <span style="color:red;">*</span></label>
  <textarea name="answer" id="answer" rows="3" class="form-control" maxlength="2000" required>{{ old('answer', $faq->answer ?? '') }}</textarea>
</div>

<label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 20px;">
  <input type="checkbox" name="is_active" value="1" {{ old('is_active', $faq->is_active ?? true) ? 'checked' : '' }}>
  <strong>ওয়েবসাইটে দেখান</strong>
</label>
