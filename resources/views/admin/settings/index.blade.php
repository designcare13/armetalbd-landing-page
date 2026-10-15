@extends('admin.layout')

@section('title', 'ল্যান্ডিং পেজ ও সেটিংস')
@section('page_title', 'ল্যান্ডিং পেজ কন্টেন্ট ও সেটিংস')

@section('content')

@php
  // Open the tab that holds the first validation error, otherwise the first tab.
  $activeTab = array_key_first($tabs);
  foreach ($errors->keys() as $errorKey) {
      foreach ($tabs as $tabId => $tab) {
          foreach ($tab['groups'] as $groupId) {
              if (array_key_exists($errorKey, $groups[$groupId]['fields'])) {
                  $activeTab = $tabId;
                  break 3;
              }
          }
      }
  }
@endphp

<style>
  .settings-tabs {
    display: flex; gap: 6px; overflow-x: auto; background: #fff; border: 1px solid var(--gray-light);
    border-radius: var(--radius); padding: 8px; margin-bottom: 20px; position: sticky; top: 0; z-index: 20;
    box-shadow: var(--shadow-sm); scrollbar-width: thin;
  }
  .settings-tab {
    flex-shrink: 0; display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border: 0;
    border-radius: 10px; background: transparent; color: #475569; font: inherit; font-size: 0.92rem;
    font-weight: 600; cursor: pointer; white-space: nowrap; transition: all 0.2s;
  }
  .settings-tab:hover { background: #f1f5f9; }
  .settings-tab.active { background: var(--primary); color: #fff; }
  .settings-panel { display: none; }
  .settings-panel.active { display: block; }
  .settings-savebar {
    position: sticky; bottom: 0; background: rgba(255,255,255,0.95); border: 1px solid var(--gray-light);
    border-radius: var(--radius); padding: 12px 16px; display: flex; align-items: center;
    justify-content: space-between; gap: 12px; flex-wrap: wrap; box-shadow: 0 -4px 12px rgba(0,0,0,0.06);
  }
</style>

<form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" id="settingsForm">
  @csrf

  <div style="max-width: 900px;">

    <div class="card" style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px;">
      <p style="font-size: 0.9rem; color: #166534; line-height: 1.6; margin: 0;">
        <i class="fa-solid fa-circle-info"></i>
        উপরের ট্যাব থেকে অংশ বেছে নিয়ে লেখা, ছবি, লোগো, ফোন/হোয়াটসঅ্যাপ ও সোশ্যাল লিংক পরিবর্তন করুন। সব ট্যাবের পরিবর্তন একসাথে নিচের "সেভ করুন" বাটনে সেভ হবে।
        প্রোডাক্ট, ক্যাটাগরি, রিভিউ ও FAQ আলাদা মেনু থেকে ম্যানেজ করুন। কোনো ঘর খালি রাখলে ওয়েবসাইটে সেই অংশ লুকানো থাকবে।
      </p>
    </div>

    <div class="settings-tabs" role="tablist">
      @foreach($tabs as $tabId => $tab)
        <button type="button" class="settings-tab {{ $tabId === $activeTab ? 'active' : '' }}" data-tab="{{ $tabId }}" role="tab">
          <i class="{{ $tab['icon'] }}"></i> {{ $tab['label'] }}
        </button>
      @endforeach
    </div>

    @foreach($tabs as $tabId => $tab)
    <div class="settings-panel {{ $tabId === $activeTab ? 'active' : '' }}" id="tab-{{ $tabId }}" role="tabpanel">
      @foreach($tab['groups'] as $groupId)
        @php $group = $groups[$groupId]; @endphp
        <div class="card">
          <div class="card-title">
            <span><i class="{{ $group['icon'] }}" style="color: var(--primary);"></i> {{ $group['title'] }}</span>
          </div>

          @foreach($group['fields'] as $key => $field)
            <div class="form-group">
              <label class="form-label" for="{{ $key }}">{{ $field['label'] }}</label>

              @if($field['type'] === 'image')
                @php $current = \App\Models\Setting::imageUrl($settings[$key]); @endphp
                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                  <img src="{{ $current }}" alt="{{ $field['label'] }}" id="preview_{{ $key }}"
                       style="width: 96px; height: 96px; object-fit: contain; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 10px; padding: 4px;{{ $current ? '' : ' display: none;' }}">
                  <div>
                    <input type="file" name="{{ $key }}" id="{{ $key }}" class="form-control" accept="image/jpeg,image/png,image/webp,.ico"
                           onchange="previewImage(this, 'preview_{{ $key }}')">
                    @if(!empty($field['hint']))
                      <small style="color: #64748b; display: block; margin-top: 4px;">{{ $field['hint'] }}</small>
                    @endif
                    <label style="display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 0.85rem; cursor: pointer;">
                      <input type="checkbox" name="remove_image[]" value="{{ $key }}">
                      আপলোড করা ছবি মুছে ডিফল্ট ছবিতে ফিরে যান
                    </label>
                  </div>
                </div>
              @elseif($field['type'] === 'product')
                <select name="{{ $key }}" id="{{ $key }}" class="form-control">
                  <option value="">ম্যানুয়াল (নিচের ঘরগুলো ব্যবহার হবে)</option>
                  @foreach($products as $productOption)
                    <option value="{{ $productOption->id }}" {{ (string) old($key, $settings[$key]) === (string) $productOption->id ? 'selected' : '' }}>{{ $productOption->name }}</option>
                  @endforeach
                </select>
              @elseif($field['type'] === 'textarea' || $field['type'] === 'list')
                <textarea name="{{ $key }}" id="{{ $key }}" rows="{{ $field['type'] === 'list' ? 4 : 3 }}" class="form-control">{{ old($key, $settings[$key]) }}</textarea>
              @else
                <input type="text" name="{{ $key }}" id="{{ $key }}" class="form-control" value="{{ old($key, $settings[$key]) }}">
              @endif

              @if($field['type'] !== 'image' && !empty($field['hint']))
                <small style="color: #64748b; display: block; margin-top: 4px;">{{ $field['hint'] }}</small>
              @endif
            </div>
          @endforeach
        </div>
      @endforeach
    </div>
    @endforeach

    <div class="settings-savebar">
      <span style="font-size: 0.85rem; color: #64748b;"><i class="fa-solid fa-layer-group"></i> সব ট্যাবের পরিবর্তন একসাথে সেভ হবে</span>
      <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-size: 1rem;">
        <i class="fa-solid fa-floppy-disk"></i> সেভ করুন
      </button>
    </div>

  </div>
</form>

<script>
  (function () {
    var tabs = document.querySelectorAll('.settings-tab');
    var panels = document.querySelectorAll('.settings-panel');
    var hasErrors = {{ $errors->any() ? 'true' : 'false' }};

    function show(id) {
      var found = false;
      panels.forEach(function (p) { var on = p.id === 'tab-' + id; p.classList.toggle('active', on); if (on) { found = true; } });
      tabs.forEach(function (t) { t.classList.toggle('active', t.dataset.tab === id); });
      return found;
    }

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        show(tab.dataset.tab);
        try { history.replaceState(null, '', '#' + tab.dataset.tab); } catch (e) {}
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    });

    var fromHash = location.hash.replace('#', '');
    if (fromHash && !hasErrors) { show(fromHash); }
  })();

  function previewImage(input, previewId) {
    var file = input.files && input.files[0];
    if (!file) { return; }
    if (!/^image\//.test(file.type)) { alert('শুধু ছবি ফাইল সিলেক্ট করুন।'); input.value = ''; return; }
    if (file.size > 2 * 1024 * 1024) { alert('ছবির সাইজ ২ MB এর বেশি হতে পারবে না।'); input.value = ''; return; }
    var preview = document.getElementById(previewId);
    preview.src = URL.createObjectURL(file);
    preview.style.display = '';
  }
</script>

@endsection
