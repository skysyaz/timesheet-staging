<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
            'date' => 'date',
            'page_views' => 'integer',
            'unique_sessions' => 'integer',
        ];
    }
}
