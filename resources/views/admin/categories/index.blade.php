@extends('admin.layout')

@section('title', 'ক্যাটাগরি ম্যানেজমেন্ট')
@section('page_title', 'প্রোডাক্ট ক্যাটাগরি')

@section('content')

<div class="card" style="margin-bottom: 24px;">
  <div class="card-title">
    <span><i class="fa-solid fa-plus-circle"></i> নতুন ক্যাটাগরি যোগ করুন</span>
  </div>

  <form action="{{ route('admin.categories.store') }}" method="POST">
    @csrf
    @include('admin.categories._fields', ['category' => null])

    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-save"></i> ক্যাটাগরি সেভ করুন
    </button>
  </form>
</div>

<div class="card">
  <div class="card-title">
    <span><i class="fa-solid fa-tags"></i> সমস্ত ক্যাটাগরি ({{ $categories->count() }} টি)</span>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>ক্রম</th>
          <th>নাম</th>
          <th>প্রোডাক্ট</th>
          <th>স্ট্যাটাস</th>
          <th>অ্যাকশন</th>
        </tr>
      </thead>
      <tbody>
        @forelse($categories as $category)
        <tr>
          <td>{{ $category->sort_order }}</td>
          <td><strong>{{ $category->name }}</strong></td>
          <td>{{ $category->products_count }} টি</td>
          <td>
            @if($category->is_active)
              <span style="background: #dcfce7; color: #166534; padding: 2px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;">সক্রিয়</span>
            @else
              <span style="background: #fee2e2; color: #991b1b; padding: 2px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;">নিষ্ক্রিয়</span>
            @endif
          </td>
          <td>
            <div style="display: flex; gap: 6px;">
              <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-secondary btn-sm" title="এডিট করুন">
                <i class="fa-solid fa-pen-to-square"></i>
              </a>
              <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" data-confirm="এই ক্যাটাগরি মুছে ফেললে এর {{ $category->products_count }} টি প্রোডাক্ট &quot;জেনারেল&quot; ক্যাটাগরিতে চলে যাবে। আপনি কি নিশ্চিত?" style="margin:0;">
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
        <tr><td colspan="5" style="text-align: center; color: #64748b; padding: 24px;">কোনো ক্যাটাগরি নেই।</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
