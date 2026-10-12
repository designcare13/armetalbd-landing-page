<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('items')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('items.product');

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
        ]);

        $wasCancelled = $order->status === 'cancelled';
        $isCancelled = $request->status === 'cancelled';

        DB::transaction(function () use ($order, $request, $wasCancelled, $isCancelled): void {
            if ($isCancelled && ! $wasCancelled) {
                $this->restoreStock($order);
            } elseif ($wasCancelled && ! $isCancelled) {
                $this->deductStock($order);
            }

            $order->update(['status' => $request->status]);
        });

        $labels = [
            'pending' => 'পেন্ডিং',
            'confirmed' => 'কনফার্মড',
            'processing' => 'প্রসেসিং',
            'shipped' => 'শিপড',
            'delivered' => 'ডেলিভারড',
            'cancelled' => 'বাতিল',
        ];

        return back()->with('success', "অর্ডারের স্ট্যাটাস সফলভাবে '{$labels[$request->status]}' হিসেবে আপডেট করা হয়েছে।");
    }

    public function destroy(Order $order)
    {
        DB::transaction(function () use ($order): void {
            if ($order->status !== 'cancelled') {
                $this->restoreStock($order);
            }

            $order->delete();
        });

        return redirect()->route('admin.orders.index')->with('success', 'অর্ডার সফলভাবে মুছে ফেলা হয়েছে।');
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $item->product?->increment('stock', $item->quantity);
        }
    }

    private function deductStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $item->product?->update(['stock' => max(0, $item->product->stock - $item->quantity)]);
        }
    }
}
