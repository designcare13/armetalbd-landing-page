@extends('admin.layout')

@section('title', 'অর্ডার এডিট - ' . $order->order_number)
@section('page_title', 'অর্ডার এডিট করুন')

@section('content')

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
  <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary btn-sm">
    <i class="fa-solid fa-arrow-left"></i> অর্ডারের বিবরণে ফিরে যান
  </a>
  <span style="font-family: var(--font-en); font-weight: 700; color: var(--primary); font-size: 1.1rem;">{{ $order->order_number }}</span>
</div>

@include('admin.orders._form', ['action' => route('admin.orders.update', $order), 'method' => 'PUT'])

@endsection
