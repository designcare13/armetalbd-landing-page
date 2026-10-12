@extends('auth.layout')

@section('title', 'রেজিস্ট্রেশন করুন')

@section('content')
<h2 class="auth-title">নতুন অ্যাকাউন্ট তৈরি</h2>
<p class="auth-sub">প্রয়োজনীয় তথ্য দিয়ে আপনার অ্যাকাউন্ট খুলুন</p>

<form action="{{ route('register') }}" method="POST">
  @csrf
  <div class="form-group">
    <label class="form-label" for="name">আপনার পূর্ণ নাম</label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
  </div>

  <div class="form-group">
    <label class="form-label" for="email">ইমেইল ঠিকানা</label>
    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
  </div>

  <div class="form-group">
    <label class="form-label" for="password">পাসওয়ার্ড</label>
    <input type="password" name="password" id="password" class="form-control" required>
  </div>

  <div class="form-group">
    <label class="form-label" for="password_confirmation">পাসওয়ার্ড নিশ্চিত করুন</label>
    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
  </div>

  <button type="submit" class="btn-submit">
    <i class="fa-solid fa-user-plus"></i> রেজিস্টার সম্পন্ন করুন
  </button>
</form>

<div class="auth-footer">
  ইতিমধ্যে অ্যাকাউন্ট আছে? <a href="{{ route('login') }}">লগইন করুন</a>
</div>
@endsection
