@extends('admin.layout')

@section('title', 'ক্যাটাগরি এডিট')
@section('page_title', 'ক্যাটাগরি এডিট করুন')

@section('content')

<div class="card" style="max-width: 800px; margin: 0 auto;">
  <div class="card-title">
    <span><i class="fa-solid fa-pen-to-square"></i> {{ $category->name }}</span>
    <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-arrow-left"></i> তালিকায় ফিরুন
    </a>
  </div>

  <form action="{{ route('admin.categories.update', $category) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.categories._fields')

    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-save"></i> আপডেট করুন
    </button>
  </form>
</div>

@endsection
