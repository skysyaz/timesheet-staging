<?php

namespace App\Support;

class FaviconAssets
{
    public const VERSION = '8';

    /** @var list<string> */
    public const FILES = [
        'favicon.ico',
        'favicon-16x16.png',
        'favicon-32x32.png',
        'apple-touch-icon.png',
    ];

    public static function directory(): string
    {
        return public_path('branding');
    }

    public static function path(string $filename): string
    {
        return self::directory() . '/' . $filename;
    }

    public static function url(string $filename): string
    {
        return asset('branding/' . $filename . '?v=' . self::VERSION);
    }

    public static function assertPresent(string $filename): string
    {
        abort_unless(in_array($filename, self::FILES, true), 404);

        $path = self::path($filename);

        abort_unless(is_file($path) && filesize($path) > 0, 404);

        return $path;
    }

    public static function mimeType(string $filename): string
    {
        return match ($filename) {
            'favicon.ico' => 'image/x-icon',
            'apple-touch-icon.png' => 'image/png',
            default => 'image/png',
        };
    }
}
