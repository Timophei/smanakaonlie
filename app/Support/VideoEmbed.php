<?php

namespace App\Support;

/**
 * Превращает обычную ссылку на видео (YouTube, VK Видео, Rutube) в адрес для встраивания.
 */
class VideoEmbed
{
    public static function url(?string $link): ?string
    {
        $link = trim((string) $link);

        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/)|youtu\.be/)([\w-]{11})~', $link, $match)) {
            return 'https://www.youtube-nocookie.com/embed/'.$match[1];
        }

        if (preg_match('~rutube\.ru/(?:video|play/embed|shorts)/([a-f0-9]{32})~', $link, $match)) {
            return 'https://rutube.ru/play/embed/'.$match[1];
        }

        if (preg_match('~(?:vk\.com|vkvideo\.ru)/(?:.*[?&]z=)?video(-?\d+)_(\d+)~', $link, $match)) {
            return "https://vk.com/video_ext.php?oid={$match[1]}&id={$match[2]}&hd=2";
        }

        if (preg_match('~vk\.com/video_ext\.php\?~', $link)) {
            return $link;
        }

        return null;
    }
}
