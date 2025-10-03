<?php
$name = isset($_GET['name']) ? $_GET['name'] : 'User';
$initials = '';
$parts = explode(' ', $name);
foreach ($parts as $p) {
    if (!empty($p)) {
        $initials .= strtoupper($p[0]);
    }
}
$initials = substr($initials, 0, 2);

$img = imagecreatetruecolor(200, 200);
$bg = imagecolorallocate($img, 0, 123, 255);
$fg = imagecolorallocate($img, 255, 255, 255);
imagefill($img, 0, 0, $bg);

$mask = imagecreatetruecolor(200, 200);
$trans = imagecolorallocate($mask, 0, 0, 0);
imagecolortransparent($mask, $trans);
$white = imagecolorallocate($mask, 255, 255, 255);
imagefilledellipse($mask, 100, 100, 200, 200, $white);
imagecopymerge($img, $mask, 0, 0, 0, 0, 200, 200, 100);

if (function_exists('imagettftext') && file_exists(__DIR__ . '/arial.ttf')) {
    $font = __DIR__ . '/arial.ttf';
    imagettftext($img, 90, 0, 35, 135, $fg, $font, $initials);
} else {
    $font_size = 5;
    $x = (imagesx($img) - imagefontwidth($font_size) * strlen($initials)) / 2;
    $y = (imagesy($img) - imagefontheight($font_size)) / 2;
    imagestring($img, $font_size, $x, $y, $initials, $fg);
}

header('Content-Type: image/png');
imagepng($img);
imagedestroy($img);
