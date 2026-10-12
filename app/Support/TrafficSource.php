<?php

namespace App\Support;

/**
 * Определяет, откуда пришёл посетитель: по адресу предыдущей страницы и меткам в ссылке.
 */
class TrafficSource
{
    /**
     * Хост (или его окончание) → [вид источника, название]. Порядок важен: Дзен раньше Яндекса.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const HOSTS = [
        'dzen.ru' => ['social', 'Дзен'],
        'zen.yandex.ru' => ['social', 'Дзен'],
        'vk.com' => ['social', 'ВКонтакте'],
        'vk.ru' => ['social', 'ВКонтакте'],
        't.me' => ['social', 'Telegram'],
        'telegram.org' => ['social', 'Telegram'],
        'ok.ru' => ['social', 'Одноклассники'],
        'instagram.com' => ['social', 'Instagram'],
        'facebook.com' => ['social', 'Facebook'],
        'wa.me' => ['social', 'WhatsApp'],
        'whatsapp.com' => ['social', 'WhatsApp'],
        'max.ru' => ['social', 'MAX'],
        'youtube.com' => ['social', 'YouTube'],
        'pinterest.com' => ['social', 'Pinterest'],
        '2gis.ru' => ['maps', '2ГИС'],
        'go.mail.ru' => ['search', 'Mail.ru'],
        'bing.com' => ['search', 'Bing'],
        'duckduckgo.com' => ['search', 'DuckDuckGo'],
        'yahoo.com' => ['search', 'Yahoo'],
        'ya.ru' => ['search', 'Яндекс'],
    ];

    /**
     * @param  array<string, mixed>  $query  параметры адреса страницы (utm_*, yclid, gclid)
     * @return array{source: string, source_name: ?string, referrer_host: ?string}
     */
    public static function detect(?string $referrer, array $query, string $ownHost): array
    {
        $host = $referrer ? strtolower((string) parse_url($referrer, PHP_URL_HOST)) : '';
        $host = preg_replace('/^(www\.|m\.|l\.|away\.)/', '', $host);
        $path = $referrer ? (string) parse_url($referrer, PHP_URL_PATH) : '';
        $utmSource = isset($query['utm_source']) ? (string) $query['utm_source'] : null;
        $medium = strtolower((string) ($query['utm_medium'] ?? ''));

        $result = fn (string $source, ?string $name) => ['source' => $source, 'source_name' => $name, 'referrer_host' => $host ?: null];

        $ownBase = preg_replace('/^(www\.|t\.)/', '', strtolower($ownHost));
        if ($host !== '' && ($host === $ownBase || str_ends_with($host, '.'.$ownBase))) {
            return $result('internal', null);
        }

        if (! empty($query['yclid'])) {
            return $result('ads', 'Яндекс Директ');
        }
        if (! empty($query['gclid'])) {
            return $result('ads', 'Google Ads');
        }
        if (in_array($medium, ['cpc', 'ppc', 'cpm', 'cpa', 'paid', 'ads'], true)) {
            return $result('ads', $utmSource ?: 'Реклама');
        }

        if ($host === '') {
            return $utmSource ? $result('campaign', $utmSource) : $result('direct', null);
        }

        foreach (self::HOSTS as $suffix => [$source, $name]) {
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                return $result($source, $name);
            }
        }

        if (preg_match('/(^|\.)yandex\.[a-z.]+$/', $host)) {
            return str_starts_with($path, '/maps') ? $result('maps', 'Яндекс Карты') : $result('search', 'Яндекс');
        }
        if (preg_match('/(^|\.)google\.[a-z.]+$/', $host)) {
            return str_starts_with($path, '/maps') ? $result('maps', 'Google Карты') : $result('search', 'Google');
        }

        return $result('referral', $host);
    }

    public static function device(string $userAgent): string
    {
        if (preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $userAgent)) {
            return 'tablet';
        }

        return preg_match('/Mobi|iPhone|iPod|Android/i', $userAgent) ? 'mobile' : 'desktop';
    }

    public static function isBot(string $userAgent): bool
    {
        return $userAgent === '' || (bool) preg_match('/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless|lighthouse|facebookexternalhit|vkshare|telegrambot/i', $userAgent);
    }
}
