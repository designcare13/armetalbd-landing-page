@extends('admin.layout')

@section('title', 'প্রশ্ন এডিট')
@section('page_title', 'প্রশ্ন এডিট করুন')

@section('content')

<div class="card" style="max-width: 900px; margin: 0 auto;">
  <div class="card-title">
    <span><i class="fa-solid fa-pen-to-square"></i> {{ \Illuminate\Support\Str::limit($faq->question, 60) }}</span>
    <a href="{{ route('admin.faqs.index') }}" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-arrow-left"></i> তালিকায় ফিরুন
    </a>
  </div>

  <form action="{{ route('admin.faqs.update', $faq) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.faqs._fields')

    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-save"></i> আপডেট করুন
    </button>
  </form>
</div>

@endsection
