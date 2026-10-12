<?php

namespace Tests\Unit;

use App\Support\VideoEmbed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VideoEmbedTest extends TestCase
{
    /**
     * @return array<string, array{string, ?string}>
     */
    public static function links(): array
    {
        return [
            'youtube' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'youtu.be' => ['https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'shorts' => ['https://youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'rutube' => ['https://rutube.ru/video/0123456789abcdef0123456789abcdef/', 'https://rutube.ru/play/embed/0123456789abcdef0123456789abcdef'],
            'vk' => ['https://vk.com/video-12345_67890', 'https://vk.com/video_ext.php?oid=-12345&id=67890&hd=2'],
            'vkvideo' => ['https://vkvideo.ru/video-12345_67890', 'https://vk.com/video_ext.php?oid=-12345&id=67890&hd=2'],
            'чужая ссылка' => ['https://example.com/video.mp4', null],
        ];
    }

    #[DataProvider('links')]
    public function test_link_becomes_embed_url(string $link, ?string $expected): void
    {
        $this->assertSame($expected, VideoEmbed::url($link));
    }
}
