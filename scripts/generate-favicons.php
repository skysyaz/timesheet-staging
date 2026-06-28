<?php

/**
 * Build cross-browser favicon assets from the Quatriz logo.
 * Run: php scripts/generate-favicons.php
 */

declare(strict_types=1);

$public = dirname(__DIR__) . '/public';
$branding = $public . '/branding';
$source = $public . '/logo.jpg';

if (! extension_loaded('gd')) {
    fwrite(STDERR, "GD extension is required.\n");
    exit(1);
}

if (! is_dir($branding) && ! mkdir($branding, 0755, true) && ! is_dir($branding)) {
    fwrite(STDERR, "Unable to create branding directory: {$branding}\n");
    exit(1);
}

if (! is_file($source)) {
    fwrite(STDERR, "Source logo not found: {$source}\n");
    exit(1);
}

$sourceImage = imagecreatefromjpeg($source);

if ($sourceImage === false) {
    fwrite(STDERR, "Unable to read source logo.\n");
    exit(1);
}

$sizes = [
    'favicon-16x16.png' => 16,
    'favicon-32x32.png' => 32,
    'apple-touch-icon.png' => 180,
];

foreach ($sizes as $filename => $size) {
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);

    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);
    $scale = min($size / $sourceWidth, $size / $sourceHeight);
    $targetWidth = (int) round($sourceWidth * $scale);
    $targetHeight = (int) round($sourceHeight * $scale);
    $offsetX = (int) floor(($size - $targetWidth) / 2);
    $offsetY = (int) floor(($size - $targetHeight) / 2);

    imagecopyresampled(
        $canvas,
        $sourceImage,
        $offsetX,
        $offsetY,
        0,
        0,
        $targetWidth,
        $targetHeight,
        $sourceWidth,
        $sourceHeight,
    );

    $path = $branding . '/' . $filename;
    imagepng($canvas, $path, 9);

    echo "Wrote {$path}\n";
}

/**
 * ICO with embedded PNG (supported by Chrome, Firefox, Safari, Edge).
 */
function writeIcoFromPng(string $pngPath, string $icoPath, int $size): void
{
    $png = file_get_contents($pngPath);

    if ($png === false) {
        throw new RuntimeException("Unable to read PNG: {$pngPath}");
    }

    $header = pack('vvv', 0, 1, 1);
    $entry = pack(
        'CCCCvvV',
        $size === 256 ? 0 : $size,
        $size === 256 ? 0 : $size,
        0,
        0,
        1,
        32,
        strlen($png),
    );
    $entry .= pack('V', 6 + 16);

    file_put_contents($icoPath, $header . $entry . $png);
}

writeIcoFromPng($branding . '/favicon-32x32.png', $branding . '/favicon.ico', 32);

echo "Wrote {$branding}/favicon.ico\n";

// Safari auto-requests /favicon.ico (no query string). Keep root copy in sync with branding.
$rootIco = $public . '/favicon.ico';
copy($branding . '/favicon.ico', $rootIco);
echo "Wrote {$rootIco}\n";

foreach (['favicon-16x16.png', 'favicon-32x32.png', 'apple-touch-icon.png'] as $legacy) {
    $legacyPath = $public . '/' . $legacy;

    if (is_file($legacyPath)) {
        unlink($legacyPath);
        echo "Removed legacy {$legacyPath}\n";
    }
}
