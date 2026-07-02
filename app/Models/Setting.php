<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public static function standardWeeklyHours(): float
    {
        return (float) static::getValue('standardWeeklyHours', 40);
    }

    public static function overtimeDailyThreshold(): ?float
    {
        $value = static::getValue('overtimeDailyThreshold');

        return $value === null ? null : (float) $value;
    }

    public static function overtimeRate(): float
    {
        return (float) static::getValue('overtimeRate', 1.5);
    }

    public static function programManagerApprovalRequired(): bool
    {
        return (bool) static::getValue('requireProgramManagerApproval', true);
    }

    public static function emailNotificationsEnabled(): bool
    {
        return (bool) static::getValue('emailNotifications', true);
    }
}
