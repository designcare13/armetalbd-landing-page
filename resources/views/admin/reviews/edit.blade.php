@extends('admin.layout')

@section('title', 'রিভিউ এডিট')
@section('page_title', 'রিভিউ এডিট করুন')

@section('content')

<div class="card" style="max-width: 900px; margin: 0 auto;">
  <div class="card-title">
    <span><i class="fa-solid fa-pen-to-square"></i> {{ $review->name }}</span>
    <a href="{{ route('admin.reviews.index') }}" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-arrow-left"></i> তালিকায় ফিরুন
    </a>
  </div>

  <form action="{{ route('admin.reviews.update', $review) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.reviews._fields')

    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-save"></i> আপডেট করুন
    </button>
  </form>
</div>

@endsection
