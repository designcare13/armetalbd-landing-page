@extends('auth.layout')

@section('title', 'পাসওয়ার্ড রিসেট')

@section('content')
<h2 class="auth-title">পাসওয়ার্ড ভুলে গেছেন?</h2>
<p class="auth-sub">আপনার অ্যাকাউন্টের ইমেইল ঠিকানা দিন, ইমেইলে রিসেট লিংক পাঠানো হবে</p>

<form action="{{ route('password.email') }}" method="POST">
  @csrf
  <div class="form-group">
    <label class="form-label" for="email">ইমেইল ঠিকানা</label>
    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus>
  </div>

  <button type="submit" class="btn-submit">
    <i class="fa-solid fa-paper-plane"></i> রিসেট লিংক পাঠান
  </button>
</form>

<div class="auth-footer">
  মনে পড়েছে? <a href="{{ route('login') }}">লগইন পেজে ফিরুন</a>
</div>
@endsection
