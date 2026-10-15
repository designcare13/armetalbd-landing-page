<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Support\ShopTime;

class DashboardController extends Controller
{
    public function index()
    {
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $confirmedOrders = Order::where('status', 'confirmed')->count();
        $deliveredOrders = Order::where('status', 'delivered')->count();

        $totalRevenue = Order::whereNotIn('status', ['cancelled'])->sum('grand_total');
        $today = [ShopTime::startOfDay(), ShopTime::endOfDay()];
        $todayOrders = Order::whereBetween('created_at', $today)->count();
        $todayRevenue = Order::whereBetween('created_at', $today)->whereNotIn('status', ['cancelled'])->sum('grand_total');

        $totalProducts = Product::count();
        $recentOrders = Order::with('items')->latest()->take(8)->get();

        return view('admin.dashboard', compact(
            'totalOrders',
            'pendingOrders',
            'confirmedOrders',
            'deliveredOrders',
            'totalRevenue',
            'todayOrders',
            'todayRevenue',
            'totalProducts',
            'recentOrders'
        ));
    }
}
