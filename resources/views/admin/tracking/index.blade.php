@extends('admin.layout')

@section('title', 'ট্র্যাকিং')
@section('page_title', 'ট্র্যাকিং ও বিজ্ঞাপনের ফল')

@section('content')

@php
  $chip = fn (bool $ok, string $text) => '<span class="status-badge '.($ok ? 'status-delivered' : 'status-pending').'">'.e($text).'</span>';
@endphp

<div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px;">
  {!! $chip($pixelId !== '', $pixelId !== '' ? 'Pixel যুক্ত' : 'Pixel ID নেই') !!}
  {!! $chip($tokenSet, $tokenSet ? 'Access token দেওয়া আছে' : 'Access token নেই') !!}
  {!! $chip($enabled, $enabled ? 'সার্ভার ট্র্যাকিং চালু' : 'সার্ভার ট্র্যাকিং বন্ধ') !!}
  {!! $chip($active, $active ? 'Meta-তে ঘটনা যাচ্ছে' : 'Meta-তে ঘটনা যাচ্ছে না') !!}
  {!! $chip($failedToday === 0, $failedToday === 0 ? 'গত ২৪ ঘণ্টায় কোনো ব্যর্থতা নেই' : "গত ২৪ ঘণ্টায় {$failedToday}টা ব্যর্থ") !!}
  {!! $chip($analyticsId !== '', $analyticsId !== '' ? 'Google Analytics চালু' : 'Google Analytics বন্ধ') !!}
  {!! $chip($clarityId !== '', $clarityId !== '' ? 'Clarity চালু' : 'Clarity বন্ধ') !!}
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 20px;">
  <div class="card" style="margin: 0;">
    <div style="font-size: 0.82rem; color: #64748b;">গত ৭ দিনে সার্ভারের ঘটনা</div>
    <div style="font-size: 1.7rem; font-weight: 700;">{{ number_format($total) }}</div>
  </div>
  <div class="card" style="margin: 0;">
    <div style="font-size: 0.82rem; color: #64748b;">Meta গ্রহণ করেছে</div>
    <div style="font-size: 1.7rem; font-weight: 700;">{{ $total > 0 ? round($sent / $total * 100) : 0 }}%</div>
  </div>
  <div class="card" style="margin: 0;">
    <div style="font-size: 0.82rem; color: #64748b;">ব্যর্থ হয়েছে</div>
    <div style="font-size: 1.7rem; font-weight: 700; color: {{ $failed > 0 ? '#b91c1c' : 'inherit' }};">{{ number_format($failed) }}</div>
  </div>
  <div class="card" style="margin: 0;">
    <div style="font-size: 0.82rem; color: #64748b;">Meta-কে জানানো বিক্রি</div>
    <div style="font-size: 1.7rem; font-weight: 700; font-family: var(--font-en);">৳{{ number_format($purchaseValue) }}</div>
  </div>
</div>

<div class="card" style="margin-bottom: 20px;">
  <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 4px;">সার্ভার ট্র্যাকিং সেটআপ</h3>
  <p style="margin: 0 0 16px; color: #64748b; font-size: 0.88rem;">
    ক্রেতার ব্রাউজার থেকে ঘটনা অনেক সময় হারায় (অ্যাড-ব্লকার, iPhone-এর নিয়ম)। সার্ভার থেকে সরাসরি Meta-কে জানালে সেই ঘটনা আর হারায় না। Pixel ID, Google Analytics আর Clarity বদলাতে
    <a href="{{ route('admin.settings.index') }}" style="color: var(--primary); font-weight: 600;">সেটিংসের SEO ও পিক্সেল ট্যাবে</a> যান।
  </p>

  <form action="{{ route('admin.tracking.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="form-group">
      <label class="form-label" for="meta_capi_token">Conversions API access token</label>
      <input type="password" name="meta_capi_token" id="meta_capi_token" class="form-control" autocomplete="off" placeholder="{{ $tokenSet ? 'সংরক্ষিত আছে, শেষ ৪ অক্ষর: '.$tokenHint.'। বদলাতে চাইলে নতুন token দিন' : 'এখানে token বসান' }}">
      <small style="color: #64748b; display: block; margin-top: 4px;">
        পাবেন: Meta Events Manager, আপনার Pixel, Settings, Conversions API, "Generate access token"। এটা গোপন রাখুন, কাউকে পাঠাবেন না। সংরক্ষণের সময় এনক্রিপ্ট হয়ে থাকে।
      </small>
      @if($tokenSet)
        <label style="display: flex; align-items: center; gap: 8px; margin-top: 8px; cursor: pointer; font-size: 0.88rem;">
          <input type="checkbox" name="remove_token" value="1"> সংরক্ষিত token মুছে ফেলুন
        </label>
      @endif
    </div>

    <div class="form-group">
      <label class="form-label" for="meta_test_event_code">Test event code (শুধু পরীক্ষার জন্য)</label>
      <input type="text" name="meta_test_event_code" id="meta_test_event_code" class="form-control" value="{{ old('meta_test_event_code', $testCode) }}" placeholder="যেমন: TEST12345" maxlength="40">
      <small style="color: #64748b; display: block; margin-top: 4px;">
        Events Manager-এর Test events ট্যাব থেকে নিন। আসল ঘটনায় এই code কখনও যায় না, শুধু নিচের পরীক্ষা বাটনে যায়, তাই এটা রেখে দিলে ক্ষতি নেই।
      </small>
    </div>

    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 18px;">
      <input type="checkbox" name="meta_capi_enabled" value="1" {{ old('meta_capi_enabled', $enabled ? '1' : '0') === '1' ? 'checked' : '' }}>
      <strong>সার্ভার ট্র্যাকিং চালু রাখুন</strong>
    </label>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> সংরক্ষণ করুন</button>
  </form>
</div>

<div class="card" style="margin-bottom: 20px;">
  <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 4px;">সংযোগ পরীক্ষা</h3>
  <p style="margin: 0 0 14px; color: #64748b; font-size: 0.88rem;">
    একটা পরীক্ষামূলক ঘটনা এখনই Meta-কে পাঠায়। এরপর Events Manager-এর Test events ট্যাবে দেখা যাবে। পরীক্ষার ঘটনা বিজ্ঞাপনের হিসাবে ধরা হয় না।
  </p>
  <form action="{{ route('admin.tracking.test') }}" method="POST" style="margin: 0;">
    @csrf
    <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-paper-plane"></i> পরীক্ষামূলক ঘটনা পাঠান</button>
  </form>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 20px;">
  <div class="card" style="margin: 0;">
    <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 12px;">গত ৭ দিন, ঘটনা অনুযায়ী</h3>
    @if($byEvent->isEmpty())
      <p style="color: #64748b; font-size: 0.9rem; margin: 0;">এখনও কোনো ঘটনা যায়নি।</p>
    @else
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>ঘটনা</th><th>গেছে</th><th>ব্যর্থ</th></tr></thead>
          <tbody>
            @foreach($byEvent as $eventName => $counts)
              <tr>
                <td style="font-family: var(--font-en); font-weight: 600;">{{ $eventName }}</td>
                <td>{{ $counts['sent'] }}</td>
                <td style="{{ $counts['failed'] > 0 ? 'color: #b91c1c; font-weight: 700;' : '' }}">{{ $counts['failed'] }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
    <p style="color: #94a3b8; font-size: 0.8rem; margin: 10px 0 0;">সার্ভার থেকে যায়: Lead, Purchase আর OrderConfirmed। বাকি ঘটনা (ViewContent, AddToCart, InitiateCheckout, Contact) ব্রাউজার থেকে যায়।</p>
  </div>

  <div class="card" style="margin: 0;">
    <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 12px;">গত ৩০ দিনের অর্ডার, কোথা থেকে এল</h3>
    @if($campaigns->isEmpty())
      <p style="color: #64748b; font-size: 0.9rem; margin: 0;">এখনও কোনো অর্ডার নেই।</p>
    @else
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>উৎস</th><th>ক্যাম্পেইন</th><th>অর্ডার</th><th>টাকা</th></tr></thead>
          <tbody>
            @foreach($campaigns as $row)
              <tr>
                <td>{{ $row->utm_source ?: 'সরাসরি বা অজানা' }}</td>
                <td style="max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $row->utm_campaign }}">{{ $row->utm_campaign ?: '-' }}</td>
                <td>{{ $row->orders }}</td>
                <td style="font-family: var(--font-en);">৳{{ number_format($row->revenue) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
    <p style="color: #94a3b8; font-size: 0.8rem; margin: 10px 0 0;">বিজ্ঞাপনের লিংকে <code>?utm_source=facebook&amp;utm_campaign=নাম</code> যোগ করলে এখানে দেখা যাবে। বাতিল অর্ডার ধরা হয় না।</p>
  </div>
</div>

<div class="card">
  <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 12px;">ঘটনার ইতিহাস</h3>
  @if($history->isEmpty())
    <p style="color: #64748b; font-size: 0.9rem; margin: 0;">কোনো ঘটনা নেই। অর্ডার বা ফোন নম্বর আসলে এখানে দেখা যাবে।</p>
  @else
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>সময়</th><th>ঘটনা</th><th>রেফারেন্স</th><th>মূল্য</th><th>অবস্থা</th><th>বিবরণ</th></tr></thead>
        <tbody>
          @foreach($history as $event)
            <tr>
              <td style="font-size: 0.82rem; color: #64748b; white-space: nowrap;">{{ $event->created_at->inShopTime()->format('d M, h:i A') }}</td>
              <td style="font-family: var(--font-en); font-weight: 600;">{{ $event->event_name }}@if($event->is_test) <span style="color: #94a3b8; font-weight: 400;">(পরীক্ষা)</span>@endif</td>
              <td style="font-family: var(--font-en); font-size: 0.85rem;">{{ $event->reference ?: '-' }}</td>
              <td style="font-family: var(--font-en);">{{ $event->value !== null ? '৳'.number_format($event->value) : '-' }}</td>
              <td>
                <span class="status-badge {{ $event->status === 'sent' ? 'status-delivered' : ($event->status === 'failed' ? 'status-cancelled' : 'status-pending') }}">
                  {{ ['sent' => 'গেছে', 'failed' => 'ব্যর্থ', 'pending' => 'পথে'][$event->status] ?? $event->status }}
                </span>
              </td>
              <td style="font-size: 0.82rem; color: #64748b; max-width: 260px;">{{ $event->error ?: ($event->fbtrace_id ? 'trace: '.$event->fbtrace_id : '') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div style="margin-top: 16px;">{{ $history->links() }}</div>
  @endif
  <p style="color: #94a3b8; font-size: 0.8rem; margin: 10px 0 0;">ইতিহাসে শুধু ঘটনার ফল থাকে, ক্রেতার কোনো তথ্য নয়। {{ strtr((string) \App\Models\TrackingEvent::RETENTION_DAYS, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']) }} দিন পর নিজে মুছে যায়।</p>
</div>

@endsection