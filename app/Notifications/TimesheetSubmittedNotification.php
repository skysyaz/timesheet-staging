<?php

namespace App\Notifications;

use App\Models\Timesheet;
use App\Support\Concerns\BuildsTimesheetMail;
use App\Support\Concerns\QueuesTimesheetNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimesheetSubmittedNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use BuildsTimesheetMail;
    use QueuesTimesheetNotification;

    public function __construct(public Timesheet $timesheet) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->timesheet->loadMissing(['user', 'project']);

        $message = (new MailMessage)
            ->subject('Timesheet submitted for your review')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A timesheet has been submitted and is waiting for Project Manager approval.');

        if ($this->timesheet->user) {
            $message->line('**Submitted by:** '.$this->timesheet->user->name);
        }

        foreach ($this->timesheetSummary($this->timesheet) as $label => $value) {
            $message->line("**{$label}:** {$value}");
        }

        return $message
            ->action('Review timesheet', $this->timesheetViewUrl($this->timesheet))
            ->line('Please review and approve or reject this submission in Quatriz TimeSheet.');
    }
}
