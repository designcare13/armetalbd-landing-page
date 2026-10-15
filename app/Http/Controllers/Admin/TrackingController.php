<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Models\TrackingEvent;
use App\Support\MetaTracker;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrackingController extends Controller
{
    public function index(): View
    {
        TrackingEvent::pruneOld();

        $settings = Setting::allWithDefaults();
        $token = Setting::secret('meta_capi_token');
        $week = TrackingEvent::where('is_test', false)->where('created_at', '>=', now()->subDays(7));

        $total = (clone $week)->count();
        $sent = (clone $week)->where('status', 'sent')->count();
        $failedToday = TrackingEvent::where('is_test', false)->where('status', 'failed')->where('created_at', '>=', now()->subDay())->count();

        $byEvent = (clone $week)
            ->selectRaw('event_name, status, count(*) as total')
            ->groupBy('event_name', 'status')
            ->get()
            ->groupBy('event_name')
            ->map(fn ($rows): array => [
                'sent' => (int) $rows->where('status', 'sent')->sum('total'),
                'failed' => (int) $rows->where('status', 'failed')->sum('total'),
                'pending' => (int) $rows->where('status', 'pending')->sum('total'),
            ]);

        return view('admin.tracking.index', [
            'pixelId' => $settings['facebook_pixel_id'],
            'analyticsId' => $settings['google_analytics_id'],
            'clarityId' => $settings['clarity_project_id'],
            'tokenSet' => $token !== null,
            'tokenHint' => $token ? Str::substr($token, -4) : null,
            'enabled' => Setting::get('meta_capi_enabled', '1') === '1',
            'testCode' => (string) Setting::get('meta_test_event_code'),
            'active' => app(MetaTracker::class)->isActive(),
            'total' => $total,
            'sent' => $sent,
            'failed' => (clone $week)->where('status', 'failed')->count(),
            'failedToday' => $failedToday,
            'purchaseValue' => (float) (clone $week)->where('event_name', 'Purchase')->where('status', 'sent')->sum('value'),
            'byEvent' => $byEvent,
            'campaigns' => Order::where('created_at', '>=', now()->subDays(30))
                ->where('status', '!=', 'cancelled')
                ->selectRaw('utm_source, utm_campaign, count(*) as orders, sum(grand_total) as revenue')
                ->groupBy('utm_source', 'utm_campaign')
                ->orderByDesc('revenue')
                ->limit(10)
                ->get(),
            'history' => TrackingEvent::latest('id')->paginate(20),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge(['meta_capi_token' => trim((string) $request->input('meta_capi_token'))]);

        $validated = $request->validate([
            'meta_capi_token' => ['nullable', 'string', 'min:20', 'max:500', 'regex:/^\S+$/'],
            'meta_test_event_code' => ['nullable', 'regex:/^[A-Za-z0-9_-]{3,40}$/'],
        ], [
            'meta_capi_token.min' => 'Access token অনেক ছোট। Events Manager থেকে পুরোটা কপি করে দিন।',
            'meta_capi_token.regex' => 'Access token-এর মাঝে ফাঁকা জায়গা থাকতে পারে না।',
            'meta_test_event_code.regex' => 'Test code ঠিক নয়। এটা সাধারণত TEST12345 ধরনের হয়।',
        ]);

        if (filled($validated['meta_capi_token'] ?? null)) {
            Setting::setSecret('meta_capi_token', $validated['meta_capi_token']);
        } elseif ($request->boolean('remove_token')) {
            Setting::setSecret('meta_capi_token', null);
        }

        Setting::set('meta_test_event_code', $validated['meta_test_event_code'] ?? '');
        Setting::set('meta_capi_enabled', $request->boolean('meta_capi_enabled') ? '1' : '0');

        return back()->with('success', 'ট্র্যাকিং সেটিংস সংরক্ষণ করা হয়েছে।');
    }

    public function test(Request $request, MetaTracker $tracker): RedirectResponse
    {
        try {
            $event = $tracker->sendTest(MetaTracker::requestContext($request));
        } catch (DomainException $e) {
            return back()->withErrors(['test' => $e->getMessage()]);
        }

        if ($event->status === 'sent') {
            return back()->with('success', 'পরীক্ষামূলক ঘটনা Meta গ্রহণ করেছে। এখন Events Manager-এর Test events ট্যাবে ঘটনাটা দেখুন।');
        }

        return back()->withErrors(['test' => 'Meta ঘটনাটা নেয়নি: '.($event->error ?: 'অজানা ত্রুটি')]);
    }
}
