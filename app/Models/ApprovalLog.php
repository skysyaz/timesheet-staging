<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalLog extends Model
{
    protected $fillable = [
        'timesheet_id', 'user_id', 'action', 'comment', 'created_at', 'updated_at',
    ];

    public function timesheet()
    {
        return $this->belongsTo(Timesheet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
