<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IncompleteOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncompleteOrderController extends Controller
{
    public function index(Request $request): View
    {
        IncompleteOrder::pruneExpired();

        $query = IncompleteOrder::notExpired()->latest('last_captured_at');

        if (array_key_exists((string) $request->input('status'), IncompleteOrder::STATUS_LABELS)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search): void {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $incompleteOrders = $query->paginate(15)->withQueryString();

        $statusCounts = IncompleteOrder::notExpired()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.incomplete-orders.index', compact('incompleteOrders', 'statusCounts'));
    }

    public function updateStatus(Request $request, IncompleteOrder $incompleteOrder): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(IncompleteOrder::STATUS_LABELS))],
        ]);

        $incompleteOrder->update($validated);

        return back()->with('success', "স্ট্যাটাস '{$incompleteOrder->status_label}' হিসেবে আপডেট করা হয়েছে।");
    }

    public function destroy(IncompleteOrder $incompleteOrder): RedirectResponse
    {
        $incompleteOrder->delete();

        return back()->with('success', 'ইনকমপ্লিট অর্ডারটি মুছে ফেলা হয়েছে।');
    }
}
