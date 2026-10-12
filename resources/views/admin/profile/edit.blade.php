@extends('admin.layout')

@section('title', 'আমার প্রোফাইল')
@section('page_title', 'প্রোফাইল ও পাসওয়ার্ড')

@section('content')

<div style="display: grid; grid-template-columns: 1fr; gap: 24px; max-width: 700px;">

  <div class="card">
    <div class="card-title">
      <span><i class="fa-solid fa-user"></i> প্রোফাইলের তথ্য</span>
    </div>

    <form action="{{ route('admin.profile.update') }}" method="POST">
      @csrf
      @method('PATCH')

      <div class="form-group">
        <label class="form-label" for="name">নাম</label>
        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $user->name) }}" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">ইমেইল ঠিকানা</label>
        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}" required>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk"></i> তথ্য সেভ করুন
      </button>
    </form>
  </div>

  <div class="card">
    <div class="card-title">
      <span><i class="fa-solid fa-key"></i> পাসওয়ার্ড পরিবর্তন</span>
    </div>

    <form action="{{ route('admin.profile.password') }}" method="POST">
      @csrf
      @method('PUT')

      <div class="form-group">
        <label class="form-label" for="current_password">বর্তমান পাসওয়ার্ড</label>
        <input type="password" name="current_password" id="current_password" class="form-control" autocomplete="current-password" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">নতুন পাসওয়ার্ড</label>
        <input type="password" name="password" id="password" class="form-control" autocomplete="new-password" required>
        <small style="color: #64748b;">কমপক্ষে ৮ অক্ষর হতে হবে।</small>
      </div>

      <div class="form-group">
        <label class="form-label" for="password_confirmation">নতুন পাসওয়ার্ড নিশ্চিত করুন</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password" required>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-lock"></i> পাসওয়ার্ড পরিবর্তন করুন
      </button>
    </form>
  </div>

</div>

@endsection
