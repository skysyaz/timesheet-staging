<?php

namespace App\Support;

use App\Models\User;
use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LocalAvatarProvider implements AvatarProvider
{
    public function get(Model | Authenticatable $record): string
    {
        $name = Filament::getNameForDefaultAvatar($record);

        $initials = Str::of($name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->map(fn (string $segment): string => mb_strtoupper(mb_substr($segment, 0, 1)))
            ->take(2)
            ->join('');

        if ($initials === '') {
            $initials = '?';
        }

        $background = $record instanceof User && filled($record->color)
            ? ltrim((string) $record->color, '#')
            : '1B3860';

        if (! preg_match('/^[0-9A-Fa-f]{6}$/', $background)) {
            $background = '1B3860';
        }

        $initials = htmlspecialchars($initials, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">
  <rect width="128" height="128" rx="64" fill="#{$background}"/>
  <text x="50%" y="50%" dy="0.35em" text-anchor="middle" fill="#FFFFFF" font-family="Inter, ui-sans-serif, system-ui, sans-serif" font-size="48" font-weight="600">{$initials}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
