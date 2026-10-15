<?php

namespace App\Support;

use App\Models\IncompleteOrder;
use App\Models\Order;
use App\Models\Setting;
use App\Models\TrackingEvent;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends server-side events to Meta (Conversions API) next to the Pixel running in the browser.
 *
 * Events leave the server after the response has been sent, so a slow or failing Meta never delays
 * or blocks an order. Every event has a stable id: the browser uses the same id, so Meta counts it once.
 */
class MetaTracker
{
    private const CURRENCY = 'BDT';

    /** Browser values we accept as-is (after trimming): campaign tags and the two Meta cookie ids. */
    public const CAMPAIGN_KEYS = ['fbp', 'fbc', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'referrer'];

    /** @var array{pixel_id: string, token: string|null, enabled: bool, test_code: string}|null */
    private ?array $settings = null;

    /**
     * @return array{pixel_id: string, token: string|null, enabled: bool, test_code: string}
     */
    public function settings(): array
    {
        return $this->settings ??= [
            'pixel_id' => trim((string) (Setting::allWithDefaults()['facebook_pixel_id'] ?? '')),
            'token' => Setting::secret('meta_capi_token'),
            'enabled' => Setting::get('meta_capi_enabled', '1') === '1',
            'test_code' => trim((string) Setting::get('meta_test_event_code')),
        ];
    }

    /**
     * True when everything needed to talk to Meta is in place and the admin has not switched it off.
     */
    public function isActive(): bool
    {
        $settings = $this->settings();

        return $settings['pixel_id'] !== '' && $settings['token'] !== null && $settings['enabled'];
    }

    /**
     * Browser supplied tracking values, cleaned. Never fails: a bad value is dropped, not an error,
     * because tracking must never be the reason an order is refused.
     *
     * @return array{fbp: string|null, fbc: string|null, utm_source: string|null, utm_medium: string|null, utm_campaign: string|null, utm_content: string|null, utm_term: string|null, referrer: string|null}
     */
    public static function clientData(Request $request): array
    {
        $text = function (string $key, int $max = 255) use ($request): ?string {
            $value = $request->input($key);

            return is_string($value) && trim($value) !== '' ? mb_substr(strip_tags(trim($value)), 0, $max) : null;
        };
        $cookie = function (string $key) use ($text): ?string {
            $value = $text($key, 200);

            return $value !== null && preg_match('/^fb\.\d\.\d+\.[A-Za-z0-9._-]+$/', $value) ? $value : null;
        };

        return [
            'fbp' => $cookie('fbp'),
            'fbc' => $cookie('fbc'),
            'utm_source' => $text('utm_source'),
            'utm_medium' => $text('utm_medium'),
            'utm_campaign' => $text('utm_campaign'),
            'utm_content' => $text('utm_content'),
            'utm_term' => $text('utm_term'),
            'referrer' => $text('referrer'),
        ];
    }

    /**
     * What the server learns from the visitor's own request: used to help Meta match the event to a person.
     *
     * @return array{ip: string|null, user_agent: string|null, source_url: string}
     */
    public static function requestContext(Request $request): array
    {
        return [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent() ? mb_substr($request->userAgent(), 0, 500) : null,
            'source_url' => $request->headers->get('referer') ?: url('/'),
        ];
    }

    /**
     * @param  array<string, mixed>  $context  from requestContext()
     */
    public function lead(IncompleteOrder $entry, array $context = []): void
    {
        $total = (float) $entry->cart_total;
        $items = collect($entry->items ?? []);

        $this->queue('Lead', 'lead-'.$entry->visitor_token, $this->userData(
            $entry->customer_phone, $entry->customer_name, $entry->delivery_area, $entry->visitor_token, $entry->fbp, $entry->fbc, $context,
        ), [
            'currency' => self::CURRENCY,
            'value' => $total,
            'content_type' => 'product',
            'content_ids' => $items->pluck('product_id')->map(fn ($id): string => (string) $id)->all(),
        ], [
            'reference' => null,
            'value' => $total,
            'source_url' => $context['source_url'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context  from requestContext(); empty for orders made by phone
     */
    public function purchase(Order $order, array $context = [], bool $byPhone = false): void
    {
        $this->queue('Purchase', 'purchase-'.$order->id, $this->orderUserData($order, $context), $this->orderCustomData($order), [
            'reference' => $order->order_number,
            'value' => (float) $order->grand_total,
            'event_time' => $order->created_at->timestamp,
            'action_source' => $byPhone ? 'phone_call' : 'website',
            'source_url' => $byPhone ? null : ($context['source_url'] ?? null),
        ]);
    }

    /**
     * A custom event for orders the team has confirmed by phone, so ads can learn from real orders
     * and not from every form submission.
     */
    public function orderConfirmed(Order $order): void
    {
        $this->queue('OrderConfirmed', 'confirmed-'.$order->id, $this->orderUserData($order), $this->orderCustomData($order), [
            'reference' => $order->order_number,
            'value' => (float) $order->grand_total,
            'action_source' => 'system_generated',
        ]);
    }

    /**
     * Send one test event right now, flagged with the Test Events code, and return the outcome.
     *
     * @param  array<string, mixed>  $context
     *
     * @throws DomainException when something needed is missing
     */
    public function sendTest(array $context = []): TrackingEvent
    {
        $settings = $this->settings();

        if ($settings['pixel_id'] === '' || $settings['token'] === null || $settings['test_code'] === '') {
            throw new DomainException('Pixel ID, access token ও test code তিনটাই দিয়ে সেভ করুন, তারপর পরীক্ষা করুন।');
        }

        $event = TrackingEvent::create([
            'event_name' => 'Lead',
            'event_id' => 'test-'.Str::uuid(),
            'reference' => 'পরীক্ষা',
            'status' => 'pending',
            'is_test' => true,
        ]);

        return $this->deliver($event, $this->payload('Lead', $event->event_id, [
            'country' => [$this->hash('bd')],
            'external_id' => [$event->event_id],
            'client_ip_address' => $context['ip'] ?? null,
            'client_user_agent' => $context['user_agent'] ?? null,
        ], ['currency' => self::CURRENCY, 'value' => 0], ['source_url' => $context['source_url'] ?? url('/')]), $settings['test_code']);
    }

    /**
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $customData
     * @param  array<string, mixed>  $options  reference, value, event_time, action_source, source_url
     */
    private function queue(string $name, string $eventId, array $userData, array $customData, array $options): void
    {
        if (! $this->isActive()) {
            return;
        }

        try {
            $event = TrackingEvent::firstOrCreate(
                ['event_name' => $name, 'event_id' => $eventId],
                ['reference' => $options['reference'] ?? null, 'value' => $options['value'] ?? null, 'status' => 'pending'],
            );
        } catch (UniqueConstraintViolationException) {
            return; // two requests raced; the other one sends it
        }

        if (! $event->wasRecentlyCreated) {
            return; // already sent, or on its way
        }

        $payload = $this->payload($name, $eventId, $userData, $customData, $options);

        app()->terminating(fn () => $this->deliver($event, $payload));
    }

    /**
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $customData
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function payload(string $name, string $eventId, array $userData, array $customData, array $options): array
    {
        return array_filter([
            'event_name' => $name,
            'event_time' => $options['event_time'] ?? now()->timestamp,
            'event_id' => $eventId,
            'action_source' => $options['action_source'] ?? 'website',
            'event_source_url' => $options['source_url'] ?? null,
            'user_data' => array_filter($userData, fn ($value): bool => filled($value)),
            'custom_data' => $customData,
        ], fn ($value): bool => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function deliver(TrackingEvent $event, array $payload, ?string $testCode = null): TrackingEvent
    {
        // Each event leaves once, even if a shutdown callback runs again.
        if ($event->status !== 'pending') {
            return $event;
        }

        $settings = $this->settings();
        $url = rtrim(config('services.meta.graph_url'), '/').'/'.config('services.meta.graph_version').'/'.$settings['pixel_id'].'/events';

        $body = ['data' => [$payload], 'access_token' => $settings['token']];

        if ($testCode !== null) {
            $body['test_event_code'] = $testCode;
        }

        try {
            $response = Http::timeout(5)->retry(2, 300, throw: false)->asJson()->post($url, $body);

            $event->update($response->successful()
                ? ['status' => 'sent', 'events_received' => $response->json('events_received'), 'fbtrace_id' => $response->json('fbtrace_id')]
                : ['status' => 'failed', 'error' => $this->describeError($response), 'fbtrace_id' => $response->json('error.fbtrace_id')]);
        } catch (Throwable $e) {
            $event->update(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 400)]);
        }

        // Housekeeping here keeps the history small without needing a scheduled job.
        if (random_int(1, 50) === 1) {
            TrackingEvent::pruneOld();
        }

        return $event;
    }

    private function describeError(Response $response): string
    {
        $message = $response->json('error.message') ?? $response->reason();
        $code = $response->json('error.code');

        return Str::limit(trim(($code ? "[{$code}] " : '').$message), 400);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function orderUserData(Order $order, array $context = []): array
    {
        return $this->userData($order->customer_phone, $order->customer_name, $order->delivery_area, $order->visitor_token, $order->fbp, $order->fbc, $context);
    }

    /**
     * Personal details are hashed (SHA-256 of the trimmed, lower-cased value) as Meta requires.
     * The visitor id is sent as is, exactly like the Pixel does, so both sides match.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function userData(string $phone, ?string $name, ?string $area, ?string $visitorToken, ?string $fbp, ?string $fbc, array $context): array
    {
        $nameParts = preg_split('/\s+/u', trim((string) $name), 2, PREG_SPLIT_NO_EMPTY) ?: [];

        return [
            'ph' => [$this->hash('88'.$phone)], // 01712345678 becomes 8801712345678
            'fn' => isset($nameParts[0]) ? [$this->hash($nameParts[0])] : null,
            'ln' => isset($nameParts[1]) ? [$this->hash($nameParts[1])] : null,
            'ct' => $area === 'inside' ? [$this->hash('dhaka')] : null,
            'country' => [$this->hash('bd')],
            'external_id' => $visitorToken ? [$visitorToken] : null,
            'client_ip_address' => $context['ip'] ?? null,
            'client_user_agent' => $context['user_agent'] ?? null,
            'fbp' => $fbp,
            'fbc' => $fbc,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderCustomData(Order $order): array
    {
        $items = $order->items;

        return [
            'currency' => self::CURRENCY,
            'value' => (float) $order->grand_total,
            'content_type' => 'product',
            'content_ids' => $items->pluck('product_id')->map(fn ($id): string => (string) $id)->all(),
            'contents' => $items->map(fn ($item): array => [
                'id' => (string) $item->product_id,
                'quantity' => $item->quantity,
                'item_price' => (float) $item->unit_price,
            ])->all(),
            'num_items' => (int) $items->sum('quantity'),
            'order_id' => $order->order_number,
        ];
    }

    private function hash(string $value): string
    {
        return hash('sha256', mb_strtolower(trim($value)));
    }
}
