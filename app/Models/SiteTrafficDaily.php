<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SiteTrafficDaily extends Model
{
    protected $table = 'site_traffic_daily';

    protected $fillable = [
        'date',
        'page_views',
        'unique_sessions',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'page_views' => 'integer',
            'unique_sessions' => 'integer',
        ];
    }

    /**
     * Persist as Y-m-d only. SQLite's default date cast stores "Y-m-d H:i:s",
     * which breaks unique lookups and assertDatabaseHas.
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?Carbon => $value !== null
                ? Carbon::parse($value)->startOfDay()
                : null,
            set: fn (mixed $value): string => Carbon::parse($value)->toDateString(),
        );
    }
}
