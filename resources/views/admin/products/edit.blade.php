@extends('admin.layout')

@section('title', 'প্রোডাক্ট সম্পাদনা - ' . $product->name)
@section('page_title', 'প্রোডাক্ট এডিট করুন')

@section('content')

<div class="card" style="max-width: 800px; margin: 0 auto;">
  <div class="card-title">
    <span><i class="fa-solid fa-pen-to-square"></i> প্রোডাক্ট তথ্য আপডেট করুন</span>
    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-arrow-left"></i> তালিকায় ফিরুন
    </a>
  </div>

  <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="form-group">
      <label class="form-label" for="name">প্রোডাক্টের নাম <span style="color:red;">*</span></label>
      <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $product->name) }}" required>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
      <div class="form-group">
        <label class="form-label" for="category_id">ক্যাটাগরি</label>
        <select name="category_id" id="category_id" class="form-control">
          <option value="">নির্বাচন করুন</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
              {{ $cat->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="badge">ব্যাজ (অপশনাল)</label>
        <input type="text" name="badge" id="badge" class="form-control" value="{{ old('badge', $product->badge) }}">
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
      <div class="form-group">
        <label class="form-label" for="price">বিক্রয় মূল্য (৳) <span style="color:red;">*</span></label>
        <input type="number" step="0.01" name="price" id="price" class="form-control" value="{{ old('price', $product->price) }}" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="old_price">পূর্বের মূল্য (৳)</label>
        <input type="number" step="0.01" name="old_price" id="old_price" class="form-control" value="{{ old('old_price', $product->old_price) }}">
      </div>

      <div class="form-group">
        <label class="form-label" for="discount_text">ছাড়ের টেক্সট (ঐচ্ছিক)</label>
        <input type="text" name="discount_text" id="discount_text" class="form-control" value="{{ old('discount_text', $product->discount_text) }}" placeholder="{{ $product->automatic_discount_label ? 'অটো: '.$product->automatic_discount_label : 'অটো হিসাব হবে' }}">
        <small style="color: #64748b;">ফাঁকা রাখলে দাম থেকে ছাড়ের শতাংশ অটো হিসাব হবে। বিশেষ অফারের টেক্সট (যেমন: ঈদ অফার) লিখলে সেটাই দেখাবে।</small>
      </div>
    </div>

    @include('admin.products._stock')

    @include('admin.products._image')

    <div class="form-group">
      <label class="form-label" for="short_description">সংক্ষিপ্ত বিবরণ</label>
      <textarea name="short_description" id="short_description" rows="3" class="form-control">{{ old('short_description', $product->short_description) }}</textarea>
    </div>

    <div class="form-group">
      <label class="form-label" for="description">বিস্তারিত বিবরণ (পপআপে দেখাবে)</label>
      <textarea name="description" id="description" rows="6" class="form-control">{{ old('description', $product->description) }}</textarea>
      <small style="color: #64748b;">ফাঁকা রাখলে পপআপে সংক্ষিপ্ত বিবরণ দেখাবে। লাইন ভেঙে লিখলে পপআপেও সেভাবেই দেখাবে।</small>
    </div>

    @include('admin.products._seo')

    <div class="form-group">
      <label class="form-label" for="specs">প্রোডাক্ট বৈশিষ্ট্যসমূহ (প্রতি লাইনে একটি করে বৈশিষ্ট্য)</label>
      <textarea name="specs" id="specs" rows="4" class="form-control">{{ old('specs', is_array($product->specs) ? implode("\n", $product->specs) : $product->specs) }}</textarea>
    </div>

    <div style="display: flex; gap: 24px; margin-bottom: 24px;">
      <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
        <strong>হোমপেজে ফিচার্ড হিসেবে দেখান</strong>
      </label>

      <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
        <strong>প্রোডাক্ট সক্রিয় রাখুন</strong>
      </label>
    </div>

    <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">
      <i class="fa-solid fa-save"></i> পরিবর্তন সংরক্ষণ করুন
    </button>
  </form>
</div>

@endsection
