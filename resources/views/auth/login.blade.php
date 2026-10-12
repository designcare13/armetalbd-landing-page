@extends('auth.layout')

@section('title', 'লগইন করুন')

@section('content')
<h2 class="auth-title">অ্যাডমিন প্যানেলে লগইন</h2>
<p class="auth-sub">আপনার ইমেইল ও পাসওয়ার্ড প্রদান করে প্রবেশ করুন</p>

<form action="{{ route('login') }}" method="POST">
  @csrf
  <div class="form-group">
    <label class="form-label" for="email">ইমেইল ঠিকানা</label>
    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus>
  </div>

  <div class="form-group">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
      <label class="form-label" for="password" style="margin-bottom:0;">পাসওয়ার্ড</label>
      <a href="{{ route('password.request') }}" style="font-size: 0.82rem; color: var(--primary); text-decoration: none;">পাসওয়ার্ড ভুলে গেছেন?</a>
    </div>
    <input type="password" name="password" id="password" class="form-control" required>
  </div>

  <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px;">
    <input type="checkbox" name="remember" id="remember">
    <label for="remember" style="font-size: 0.88rem; color: #475569; cursor: pointer;">লগইন তথ্য মনে রাখুন</label>
  </div>

  <button type="submit" class="btn-submit">
    <i class="fa-solid fa-right-to-bracket"></i> লগইন করুন
  </button>
</form>

<div class="auth-footer">
  @if($canRegister ?? false)
    প্রথমবার সেটআপ করছেন? <a href="{{ route('register') }}">অ্যাডমিন অ্যাকাউন্ট তৈরি করুন</a>
  @endif
  <div style="margin-top: 14px;">
    <a href="{{ url('/') }}" style="color: #64748b; font-size: 0.82rem;"><i class="fa-solid fa-arrow-left"></i> হোমপেজে ফিরে যান</a>
  </div>
</div>
@endsection
