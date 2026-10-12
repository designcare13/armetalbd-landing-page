@extends('auth.layout')

@section('title', 'নতুন পাসওয়ার্ড সেট করুন')

@section('content')
<h2 class="auth-title">নতুন পাসওয়ার্ড সেট করুন</h2>
<p class="auth-sub">আপনার অ্যাকাউন্টের জন্য নতুন একটি পাসওয়ার্ড দিন</p>

<form action="{{ route('password.update') }}" method="POST">
  @csrf
  <input type="hidden" name="token" value="{{ $token }}">

  <div class="form-group">
    <label class="form-label" for="email">ইমেইল ঠিকানা</label>
    <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $email) }}" required>
  </div>

  <div class="form-group">
    <label class="form-label" for="password">নতুন পাসওয়ার্ড</label>
    <input type="password" name="password" id="password" class="form-control" required autofocus>
  </div>

  <div class="form-group">
    <label class="form-label" for="password_confirmation">নতুন পাসওয়ার্ড নিশ্চিত করুন</label>
    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
  </div>

  <button type="submit" class="btn-submit">
    <i class="fa-solid fa-lock"></i> পাসওয়ার্ড পরিবর্তন করুন
  </button>
</form>

<div class="auth-footer">
  <a href="{{ route('login') }}">লগইন পেজে ফিরে যান</a>
</div>
@endsection
