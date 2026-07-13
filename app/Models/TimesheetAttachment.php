<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TimesheetAttachment extends Model
{
    protected $fillable = [
        'timesheet_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Remove the underlying file whenever the record is deleted, whether
        // that happens directly or as part of a timesheet being removed.
        static::deleting(function (TimesheetAttachment $attachment): void {
            $disk = $attachment->disk ?: 'local';

            if ($attachment->path && Storage::disk($disk)->exists($attachment->path)) {
                Storage::disk($disk)->delete($attachment->path);
            }
        });
    }

    public function timesheet()
    {
        return $this->belongsTo(Timesheet::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function humanSize(): string
    {
        $size = (int) $this->size;

        if ($size <= 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($size, 1024)), count($units) - 1);
        $value = $size / (1024 ** $power);

        return ($power === 0 ? $size : number_format($value, 1)).' '.$units[$power];
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
