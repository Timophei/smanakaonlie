<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use App\Support\TrafficSource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Приём просмотров со страниц сайта (navigator.sendBeacon из шаблона).
 */
class AnalyticsController extends Controller
{
    public function hit(Request $request): Response
    {
        $userAgent = (string) $request->userAgent();
        $path = (string) $request->input('p', '');

        if (auth()->check() || ! SeoController::isMainHost($request) || TrafficSource::isBot($userAgent)
            || ! str_starts_with($path, '/') || strlen($path) > 255) {
            return response()->noContent();
        }

        parse_str(ltrim((string) $request->input('q', ''), '?'), $query);
        $referrer = Str::limit((string) $request->input('r', ''), 500, '') ?: null;
        $source = TrafficSource::detect($referrer, $query, $request->getHost());
        $now = now(config('app.site_timezone'));
        $utm = fn (string $key): ?string => isset($query[$key]) && is_string($query[$key]) ? Str::limit($query[$key], 150, '') : null;

        PageView::create([
            'created_at' => now(),
            'day' => $now->toDateString(),
            'path' => $path === '/' ? '/' : '/'.trim($path, '/').'/',
            // Сутки + ключ приложения + IP + браузер: посетителя можно посчитать, но не опознать
            'visitor' => substr(hash('sha256', $now->toDateString().config('app.key').$request->ip().$userAgent), 0, 16),
            'is_entry' => $source['source'] !== 'internal',
            ...$source,
            'referrer' => $source['source'] === 'internal' ? null : $referrer,
            'utm_source' => $utm('utm_source'),
            'utm_medium' => $utm('utm_medium'),
            'utm_campaign' => $utm('utm_campaign'),
            'utm_content' => $utm('utm_content'),
            'utm_term' => $utm('utm_term'),
            'device' => TrafficSource::device($userAgent),
        ]);

        return response()->noContent();
    }
}
