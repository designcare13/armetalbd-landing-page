@extends('admin.layout')

@section('title', 'নতুন অর্ডার যোগ করুন')
@section('page_title', 'নতুন অর্ডার যোগ করুন')

@section('content')

<div style="margin-bottom: 20px;">
  <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-sm">
    <i class="fa-solid fa-arrow-left"></i> অর্ডার তালিকায় ফিরে যান
  </a>
</div>

<p style="margin: 0 0 18px; color: #64748b; font-size: 0.9rem;">
  ফোন, ফেসবুক, হোয়াটসঅ্যাপ বা শোরুমে পাওয়া অর্ডার এখানে নিজে যোগ করুন। ওয়েবসাইটের অর্ডারের মতোই এতে স্টক কমবে এবং অর্ডার তালিকায় দেখা যাবে।
</p>

@include('admin.orders._form', ['action' => route('admin.orders.store'), 'method' => 'POST'])

@endsection
