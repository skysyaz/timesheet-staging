<?php

namespace App\Http\Controllers;

use App\Models\TimesheetAttachment;
use App\Support\TimesheetAccess;
use Illuminate\Support\Facades\Storage;

class TimesheetAttachmentController extends Controller
{
    public function download(TimesheetAttachment $attachment)
    {
        $user = auth()->user();
        $attachment->loadMissing('timesheet.project');

        if (! $user || ! $attachment->timesheet
            || ! TimesheetAccess::userCanViewTimesheet($user, $attachment->timesheet)) {
            abort(403);
        }

        $disk = $attachment->disk ?: 'local';

        if (! $attachment->path || ! Storage::disk($disk)->exists($attachment->path)) {
            abort(404);
        }

        return Storage::disk($disk)->download($attachment->path, $attachment->original_name);
    }
}
