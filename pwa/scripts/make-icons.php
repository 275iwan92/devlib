<?php
// Membuat public/icon-192.png dan icon-512.png (sama dengan icon.svg: latar #1f6feb, "DL" putih tebal; teks di zona aman maskable).
// Pakai: php scripts/make-icons.php <berkas-font-tebal.ttf>   (contoh Windows: C:\Windows\Fonts\arialbd.ttf)
$font = $argv[1] ?? exit("Beri jalur berkas font TTF tebal.\n");
foreach ([192, 512] as $s) {
    $im = imagecreatetruecolor($s, $s);
    imagefill($im, 0, 0, imagecolorallocate($im, 0x1f, 0x6f, 0xeb));
    $size = 240 * 0.75 * $s / 512; // GD memakai poin (96 dpi): 0,75 x piksel SVG
    $b = imagettfbbox($size, 0, $font, 'DL');
    $x = (int) round(($s - ($b[2] - $b[0])) / 2 - $b[0]);
    imagettftext($im, $size, 0, $x, (int) round(330 * $s / 512), imagecolorallocate($im, 255, 255, 255), $font, 'DL');
    imagepng($im, __DIR__ . "/../public/icon-$s.png");
}
