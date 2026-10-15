<div class="form-group" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; background: #f8fafc;">
  <div class="form-label" style="margin-bottom: 4px;"><i class="fa-brands fa-google"></i> গুগল সার্চে যেভাবে দেখাবে (ঐচ্ছিক)</div>
  <small style="color: #64748b; display: block; margin-bottom: 12px;">ফাঁকা রাখলে প্রোডাক্টের নাম, দাম ও বিবরণ থেকে অটো তৈরি হবে। নিজে লিখলে গুগলে ঠিক সেটাই দেখাবে।</small>

  <div class="form-group">
    <label class="form-label" for="seo_title">সার্চ টাইটেল</label>
    <input type="text" name="seo_title" id="seo_title" class="form-control" maxlength="120" value="{{ old('seo_title', $product->seo_title ?? '') }}" placeholder="যেমন: দোলনা সুইং চেয়ার দাম - ক্যাশ অন ডেলিভারি | AR Metal BD">
    <small style="color: #64748b;"><span id="seo_title_count">০</span> অক্ষর। ৬০ এর মধ্যে রাখলে গুগলে পুরোটা দেখা যায়। সম্ভব হলে ব্র্যান্ডের নাম শেষে দিন।</small>
  </div>

  <div class="form-group">
    <label class="form-label" for="seo_description">সার্চ বিবরণ</label>
    <textarea name="seo_description" id="seo_description" rows="3" class="form-control" maxlength="320" placeholder="যেমন: নিজস্ব কারখানায় তৈরি মজবুত দোলনা সুইং চেয়ার। সারা দেশে ক্যাশ অন ডেলিভারি, ১ বছরের সার্ভিস ওয়ারেন্টি।">{{ old('seo_description', $product->seo_description ?? '') }}</textarea>
    <small style="color: #64748b;"><span id="seo_description_count">০</span> অক্ষর। ১৬০ এর মধ্যে রাখলে গুগলে পুরোটা দেখা যায়।</small>
  </div>

  <div id="seo_preview" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
    <div style="font-size: 12px; color: #64748b;">প্রিভিউ</div>
    <div id="seo_preview_url" style="font-size: 13px; color: #1a7f37; word-break: break-all;"></div>
    <div id="seo_preview_title" style="font-size: 18px; color: #1a0dab; line-height: 1.3;"></div>
    <div id="seo_preview_description" style="font-size: 13px; color: #4d5156; line-height: 1.5;"></div>
  </div>
</div>

<script>
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var digits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
  var bangla = function (n) { return String(n).replace(/\d/g, function (d) { return digits[d]; }); };
  var site = @json(\App\Support\Seo::baseUrl(['site_url' => \App\Models\Setting::get('site_url')]));
  var slug = @json($product->slug ?? null);

  function refresh() {
    var title = $('seo_title').value.trim();
    var description = $('seo_description').value.trim();
    var fallbackDescription = ($('short_description') && $('short_description').value.trim()) || ($('description') && $('description').value.trim()) || '';

    $('seo_title_count').textContent = bangla($('seo_title').value.length);
    $('seo_description_count').textContent = bangla($('seo_description').value.length);
    $('seo_title_count').style.color = $('seo_title').value.length > 60 ? '#b45309' : '';
    $('seo_description_count').style.color = $('seo_description').value.length > 160 ? '#b45309' : '';

    $('seo_preview_url').textContent = site + '/products/' + (slug || 'প্রোডাক্টের-নাম');
    $('seo_preview_title').textContent = title || (($('name').value.trim() || 'প্রোডাক্টের নাম') + ' (অটো টাইটেল)');
    $('seo_preview_description').textContent = (description || fallbackDescription || 'অটো বিবরণ তৈরি হবে।').slice(0, 160);
  }

  ['seo_title', 'seo_description', 'name', 'short_description', 'description'].forEach(function (id) {
    if ($(id)) { $(id).addEventListener('input', refresh); }
  });
  refresh();
})();
</script>
