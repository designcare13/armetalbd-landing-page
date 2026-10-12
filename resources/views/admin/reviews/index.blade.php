@extends('admin.layout')

@section('title', 'কাস্টমার রিভিউ')
@section('page_title', 'কাস্টমার রিভিউ ম্যানেজমেন্ট')

@section('content')

<div class="card" style="margin-bottom: 24px;">
  <div class="card-title">
    <span><i class="fa-solid fa-plus-circle"></i> নতুন রিভিউ যোগ করুন</span>
  </div>

  <form action="{{ route('admin.reviews.store') }}" method="POST">
    @csrf
    @include('admin.reviews._fields', ['review' => null])

    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-save"></i> রিভিউ সেভ করুন
    </button>
  </form>
</div>

<div class="card">
  <div class="card-title">
    <span><i class="fa-solid fa-star"></i> সমস্ত রিভিউ ({{ $reviews->count() }} টি)</span>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>ক্রম</th>
          <th>গ্রাহক</th>
          <th>রিভিউ</th>
          <th>রেটিং</th>
          <th>স্ট্যাটাস</th>
          <th>অ্যাকশন</th>
        </tr>
      </thead>
      <tbody>
        @forelse($reviews as $review)
        <tr>
          <td>{{ $review->sort_order }}</td>
          <td>
            <strong>{{ $review->name }}</strong>
            @if($review->location)<div style="font-size: 0.8rem; color: #64748b;">{{ $review->location }}</div>@endif
          </td>
          <td style="max-width: 360px;">{{ \Illuminate\Support\Str::limit($review->body, 90) }}</td>
          <td style="color: #f59e0b;">{{ str_repeat('★', $review->rating) }}</td>
          <td>
            @if($review->is_active)
              <span style="background: #dcfce7; color: #166534; padding: 2px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;">সক্রিয়</span>
            @else
              <span style="background: #fee2e2; color: #991b1b; padding: 2px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;">লুকানো</span>
            @endif
          </td>
          <td>
            <div style="display: flex; gap: 6px;">
              <a href="{{ route('admin.reviews.edit', $review) }}" class="btn btn-secondary btn-sm" title="এডিট করুন">
                <i class="fa-solid fa-pen-to-square"></i>
              </a>
              <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" data-confirm="আপনি কি নিশ্চিত এই রিভিউটি মুছে ফেলতে চান?" style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" title="মুছে ফেলুন">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align: center; color: #64748b; padding: 24px;">কোনো রিভিউ নেই। রিভিউ না থাকলে ওয়েবসাইটে রিভিউ সেকশন দেখাবে না।</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
