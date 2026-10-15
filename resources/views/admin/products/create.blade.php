@extends('admin.layout')

@section('title', 'নতুন প্রোডাক্ট যোগ করুন')
@section('page_title', 'নতুন প্রোডাক্ট তৈরি')

@section('content')

<div class="card" style="max-width: 800px; margin: 0 auto;">
  <div class="card-title">
    <span><i class="fa-solid fa-plus-circle"></i> প্রোডাক্টের তথ্য পূরণ করুন</span>
    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-arrow-left"></i> তালিকায় ফিরুন
    </a>
  </div>

  <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="form-group">
      <label class="form-label" for="name">প্রোডাক্টের নাম <span style="color:red;">*</span></label>
      <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" placeholder="যেমন: প্রিমিয়াম দোলনা সুইং চেয়ার" required>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
      <div class="form-group">
        <label class="form-label" for="category_id">ক্যাটাগরি</label>
        <select name="category_id" id="category_id" class="form-control">
          <option value="">নির্বাচন করুন</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="badge">ব্যাজ (অপশনাল)</label>
        <input type="text" name="badge" id="badge" class="form-control" value="{{ old('badge') }}" placeholder="যেমন: হট সেলিং / সেরা পছন্দ">
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
      <div class="form-group">
        <label class="form-label" for="price">বিক্রয় মূল্য (৳) <span style="color:red;">*</span></label>
        <input type="number" step="0.01" name="price" id="price" class="form-control" value="{{ old('price') }}" placeholder="13650" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="old_price">পূর্বের মূল্য (৳)</label>
        <input type="number" step="0.01" name="old_price" id="old_price" class="form-control" value="{{ old('old_price') }}" placeholder="16500">
      </div>

      <div class="form-group">
        <label class="form-label" for="discount_text">ছাড়ের টেক্সট (ঐচ্ছিক)</label>
        <input type="text" name="discount_text" id="discount_text" class="form-control" value="{{ old('discount_text') }}" placeholder="অটো হিসাব হবে">
        <small style="color: #64748b;">ফাঁকা রাখলে দাম থেকে ছাড়ের শতাংশ অটো হিসাব হবে। বিশেষ অফারের টেক্সট (যেমন: ঈদ অফার) লিখলে সেটাই দেখাবে।</small>
      </div>
    </div>

    @include('admin.products._stock')

    @include('admin.products._image')

    <div class="form-group">
      <label class="form-label" for="short_description">সংক্ষিপ্ত বিবরণ</label>
      <textarea name="short_description" id="short_description" rows="3" class="form-control" placeholder="প্রোডাক্ট সম্পর্কে ২/৩ লাইনের বিবরণ...">{{ old('short_description') }}</textarea>
    </div>

    <div class="form-group">
      <label class="form-label" for="description">বিস্তারিত বিবরণ (পপআপে দেখাবে)</label>
      <textarea name="description" id="description" rows="6" class="form-control" placeholder="প্রোডাক্টের পূর্ণ বিবরণ লিখুন...">{{ old('description') }}</textarea>
      <small style="color: #64748b;">ফাঁকা রাখলে পপআপে সংক্ষিপ্ত বিবরণ দেখাবে। লাইন ভেঙে লিখলে পপআপেও সেভাবেই দেখাবে।</small>
    </div>

    <div class="form-group">
      <label class="form-label" for="specs">প্রোডাক্ট বৈশিষ্ট্যসমূহ (প্রতি লাইনে একটি করে বৈশিষ্ট্য লিখুন)</label>
      <textarea name="specs" id="specs" rows="4" class="form-control" placeholder="উপাদান: এম.এস আইরন স্টিল ও চাইনিজ ফাইবার&#10;ফিনিশিং: রোদে-বৃষ্টিতে নষ্ট না হওয়া হিট পেইন্ট&#10;লোড ক্যাপাসিটি: ১৮০ কেজি পর্যন্ত">{{ old('specs') }}</textarea>
    </div>

    <div style="display: flex; gap: 24px; margin-bottom: 24px;">
      <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}>
        <strong>হোমপেজে ফিচার্ড হিসেবে দেখান</strong>
      </label>

      <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
        <input type="checkbox" name="is_active" value="1" checked>
        <strong>প্রোডাক্ট সক্রিয় রাখুন</strong>
      </label>
    </div>

    <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">
      <i class="fa-solid fa-save"></i> প্রোডাক্ট সেভ করুন
    </button>
  </form>
</div>

@endsection
