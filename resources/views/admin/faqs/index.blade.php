@extends('admin.layout')

@section('title', 'FAQ ম্যানেজমেন্ট')
@section('page_title', 'প্রশ্ন ও উত্তর (FAQ)')

@section('content')

<div class="card" style="margin-bottom: 24px;">
  <div class="card-title">
    <span><i class="fa-solid fa-plus-circle"></i> নতুন প্রশ্ন যোগ করুন</span>
  </div>

  <form action="{{ route('admin.faqs.store') }}" method="POST">
    @csrf
    @include('admin.faqs._fields', ['faq' => null])

    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-save"></i> প্রশ্ন সেভ করুন
    </button>
  </form>
</div>

<div class="card">
  <div class="card-title">
    <span><i class="fa-solid fa-circle-question"></i> সমস্ত প্রশ্ন ({{ $faqs->count() }} টি)</span>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>ক্রম</th>
          <th>প্রশ্ন</th>
          <th>উত্তর</th>
          <th>স্ট্যাটাস</th>
          <th>অ্যাকশন</th>
        </tr>
      </thead>
      <tbody>
        @forelse($faqs as $faq)
        <tr>
          <td>{{ $faq->sort_order }}</td>
          <td><strong>{{ $faq->question }}</strong></td>
          <td style="max-width: 360px;">{{ \Illuminate\Support\Str::limit($faq->answer, 90) }}</td>
          <td>
            @if($faq->is_active)
              <span style="background: #dcfce7; color: #166534; padding: 2px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;">সক্রিয়</span>
            @else
              <span style="background: #fee2e2; color: #991b1b; padding: 2px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;">লুকানো</span>
            @endif
          </td>
          <td>
            <div style="display: flex; gap: 6px;">
              <a href="{{ route('admin.faqs.edit', $faq) }}" class="btn btn-secondary btn-sm" title="এডিট করুন">
                <i class="fa-solid fa-pen-to-square"></i>
              </a>
              <form action="{{ route('admin.faqs.destroy', $faq) }}" method="POST" data-confirm="আপনি কি নিশ্চিত এই প্রশ্নটি মুছে ফেলতে চান?" style="margin:0;">
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
        <tr><td colspan="5" style="text-align: center; color: #64748b; padding: 24px;">কোনো প্রশ্ন নেই। প্রশ্ন না থাকলে ওয়েবসাইটে FAQ সেকশন দেখাবে না।</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
