<?php

namespace Tests\Unit;

use App\Support\TrafficSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TrafficSourceTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: array<string, string>, 2: string, 3: ?string}>
     */
    public static function referrers(): array
    {
        return [
            'прямой заход' => [null, [], 'direct', null],
            'Яндекс' => ['https://yandex.ru/search/?text=шаманка', [], 'search', 'Яндекс'],
            'Яндекс Карты' => ['https://yandex.ru/maps/org/123', [], 'maps', 'Яндекс Карты'],
            'Google' => ['https://www.google.com/', [], 'search', 'Google'],
            'Дзен раньше Яндекса' => ['https://dzen.ru/a/xyz', [], 'social', 'Дзен'],
            'ВКонтакте через away' => ['https://away.vk.com/away.php?to=x', [], 'social', 'ВКонтакте'],
            'Telegram' => ['https://t.me/shamanka', [], 'social', 'Telegram'],
            '2ГИС' => ['https://2gis.ru/irkutsk', [], 'maps', '2ГИС'],
            'свой сайт' => ['https://shamanka.online/stati/tropa/', [], 'internal', null],
            'тестовый поддомен' => ['https://t.shamanka.online/', [], 'internal', null],
            'чужой сайт' => ['https://www.baikal-forum.ru/topic/1', [], 'referral', 'baikal-forum.ru'],
            'метка без реферера' => [null, ['utm_source' => 'vk_post'], 'campaign', 'vk_post'],
            'Директ' => ['https://yandex.ru/', ['yclid' => '123'], 'ads', 'Яндекс Директ'],
            'платная метка' => [null, ['utm_source' => 'vk', 'utm_medium' => 'cpc'], 'ads', 'vk'],
        ];
    }

    #[DataProvider('referrers')]
    public function test_detects_source(?string $referrer, array $query, string $source, ?string $name): void
    {
        $result = TrafficSource::detect($referrer, $query, 'shamanka.online');

        $this->assertSame($source, $result['source']);
        $this->assertSame($name, $result['source_name']);
    }

    public function test_devices_and_bots(): void
    {
        $this->assertSame('mobile', TrafficSource::device('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile/15E148'));
        $this->assertSame('tablet', TrafficSource::device('Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X)'));
        $this->assertSame('desktop', TrafficSource::device('Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) Safari/605.1.15'));
        $this->assertTrue(TrafficSource::isBot('Mozilla/5.0 (compatible; YandexBot/3.0)'));
        $this->assertFalse(TrafficSource::isBot('Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) Safari/605.1.15'));
    }
}
