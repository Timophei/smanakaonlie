<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * QR-код в SVG — векторный, для чёткой печати. Уровень коррекции H (30 %),
 * поэтому в центр можно положить значок Шаманки и код всё равно считается.
 */
class QrCode
{
    public static function svg(string $text, int $size = 400): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 0), new SvgImageBackEnd));
        $svg = $writer->writeString($text, 'ISO-8859-1', ErrorCorrectionLevel::H()); // адреса латиницей — без лишнего признака кодировки, читают и старые сканеры

        return trim(preg_replace('/^<\?xml[^>]*>\s*/', '', $svg));
    }
}
