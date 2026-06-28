<?php

namespace App\Http\Controllers;

use App\Support\FaviconAssets;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FaviconController extends Controller
{
    public function branding(string $file): BinaryFileResponse
    {
        $path = FaviconAssets::assertPresent($file);

        return $this->fileResponse($path, FaviconAssets::mimeType($file));
    }

    public function legacyIco(): BinaryFileResponse
    {
        $rootPath = public_path('favicon.ico');

        if (is_file($rootPath) && filesize($rootPath) > 0) {
            return $this->fileResponse($rootPath, 'image/x-icon');
        }

        $path = FaviconAssets::assertPresent('favicon.ico');

        return $this->fileResponse($path, 'image/x-icon');
    }

    public function legacyAppleTouch(): BinaryFileResponse
    {
        $path = FaviconAssets::assertPresent('apple-touch-icon.png');

        return $this->fileResponse($path, 'image/png');
    }

    private function fileResponse(string $path, string $contentType): BinaryFileResponse
    {
        $response = response()->file($path, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=604800, stale-while-revalidate=86400',
        ]);

        $response->setLastModified(new \DateTimeImmutable('@' . filemtime($path)));
        $response->setEtag(sha1_file($path) ?: (string) filemtime($path));

        return $response;
    }

    public function manifest(): Response
    {
        $icons = [
            [
                'src' => FaviconAssets::url('favicon-32x32.png'),
                'sizes' => '32x32',
                'type' => 'image/png',
            ],
            [
                'src' => FaviconAssets::url('apple-touch-icon.png'),
                'sizes' => '180x180',
                'type' => 'image/png',
            ],
        ];

        $body = json_encode([
            'name' => config('app.name', 'Quatriz TimeSheet'),
            'short_name' => 'TimeSheet',
            'icons' => $icons,
            'display' => 'standalone',
            'start_url' => '/admin',
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return response($body, 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
