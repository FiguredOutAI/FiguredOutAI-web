<?php
/**
 * Branded link-preview images (1200x630 PNG).
 * Usage: og.php?t=<title>&k=<kicker>&s=<signature>. Use og_image_url() in lib.php to build the link.
 * The signature stops anyone else from generating arbitrary images under our brand.
 * Images are cached in uploads/og/.
 */
require __DIR__ . '/app/lib.php';

$t = mb_substr(trim((string)($_GET['t'] ?? '')), 0, 140);
$k = mb_substr(trim((string)($_GET['k'] ?? '')), 0, 40);
$s = (string)($_GET['s'] ?? '');
if ($t === '' || !hash_equals(og_sig($t, $k), $s)) { http_response_code(404); exit; }

$dir = __DIR__ . '/uploads/og';
$file = $dir . '/' . $s . '.png';
if (!is_file($file)) {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $png = og_render($t, $k);
    if ($png === null) { header('Location: ' . url('assets/img/og-image.jpg'), true, 302); exit; }
    @file_put_contents($file, $png, LOCK_EX);
}
header('Content-Type: image/png');
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Robots-Tag: noindex');
readfile($file);

function og_render(string $title, string $kicker): ?string {
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagettftext')) return null;
    $F = __DIR__ . '/app/fonts/';
    $bold = $F . 'geist-semibold.ttf'; $reg = $F . 'geist-regular.ttf'; $mono = $F . 'geist-mono.ttf'; $serif = $F . 'instrument-serif-italic.ttf';
    foreach ([$bold, $reg, $mono] as $f) if (!is_file($f)) return null;
    $W = 1200; $H = 630;
    $im = imagecreatetruecolor($W, $H);
    imagealphablending($im, true);

    // Background: deep vertical gradient
    for ($y = 0; $y < $H; $y++) {
        $p = $y / $H;
        $c = imagecolorallocate($im, (int)(7 - 3 * $p), (int)(12 - 5 * $p), (int)(16 - 6 * $p));
        imageline($im, 0, $y, $W, $y, $c);
    }
    // Soft cyan glow in the top-right corner
    for ($r = 560; $r > 0; $r -= 14) {
        $c = imagecolorallocatealpha($im, 34, 211, 238, 125);
        imagefilledellipse($im, 1120, -20, $r * 2, $r * 2, $c);
    }
    // Faint grid lines
    $grid = imagecolorallocatealpha($im, 255, 255, 255, 122);
    for ($x = 0; $x < $W; $x += 60) imageline($im, $x, 0, $x, $H, $grid);
    for ($y = 0; $y < $H; $y += 60) imageline($im, 0, $y, $W, $y, $grid);

    $white = imagecolorallocate($im, 240, 245, 248);
    $muted = imagecolorallocate($im, 138, 154, 166);
    $cyan = imagecolorallocate($im, 34, 211, 238);

    // Logo mark and wordmark
    $mark = @imagecreatefrompng(__DIR__ . '/assets/img/logo-mark.png');
    if ($mark) {
        $mw = imagesx($mark); $mh = imagesy($mark); $th = 52; $tw = (int)round($mw * $th / $mh);
        imagecopyresampled($im, $mark, 72, 64, 0, 0, $tw, $th, $mw, $mh);
        imagedestroy($mark);
        $wx = 72 + $tw + 16;
    } else $wx = 72;
    imagettftext($im, 30, 0, $wx, 102, $white, $bold, 'FiguredOutAI');

    // Kicker
    $y = 214;
    if ($kicker !== '') {
        imagefilledrectangle($im, 72, $y - 20, 72 + 28, $y - 17, $cyan);
        imagettftext($im, 17, 0, 112, $y - 8, $cyan, $mono, mb_strtoupper($kicker));
        $y += 34;
    }

    // Title: shrink until it fits in 3 lines
    $maxW = 1020;
    foreach ([64, 58, 52, 46, 40] as $size) {
        $lines = og_wrap($title, $bold, $size, $maxW);
        if (count($lines) <= 3) break;
    }
    if (count($lines) > 3) { $lines = array_slice($lines, 0, 3); $lines[2] = rtrim($lines[2], ' ,.:;') . '…'; }
    $lh = (int)round($size * 1.22);
    $y += $size;
    foreach ($lines as $ln) { imagettftext($im, $size, 0, 72, $y, $white, $bold, $ln); $y += $lh; }

    // Footer
    imagefilledrectangle($im, 72, 540, $W - 72, 540, imagecolorallocatealpha($im, 255, 255, 255, 110));
    imagettftext($im, 20, 0, 72, 582, $muted, $reg, 'figuredoutai.com');
    $tag = 'AI-native infrastructure for businesses that intend to lead.';
    $font = is_file($serif) ? $serif : $reg;
    $bb = imagettfbbox(22, 0, $font, $tag);
    imagettftext($im, 22, 0, $W - 72 - ($bb[2] - $bb[0]), 584, $muted, $font, $tag);

    ob_start(); imagepng($im, null, 6); $out = ob_get_clean();
    imagedestroy($im);
    return $out;
}

function og_wrap(string $text, string $font, int $size, int $maxW): array {
    $words = preg_split('/\s+/u', $text); $lines = []; $cur = '';
    foreach ($words as $w) {
        $try = $cur === '' ? $w : $cur . ' ' . $w;
        $bb = imagettfbbox($size, 0, $font, $try);
        if (($bb[2] - $bb[0]) > $maxW && $cur !== '') { $lines[] = $cur; $cur = $w; } else $cur = $try;
    }
    if ($cur !== '') $lines[] = $cur;
    return $lines;
}
