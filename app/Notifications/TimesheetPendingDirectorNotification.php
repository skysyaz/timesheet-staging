<?php

namespace App\Notifications;

use App\Models\Timesheet;
use App\Support\Concerns\BuildsTimesheetMail;
use App\Support\Concerns\QueuesTimesheetNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimesheetPendingDirectorNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use BuildsTimesheetMail;
    use QueuesTimesheetNotification;

    public function __construct(
        public Timesheet $timesheet,
        public ?string $pmComment = null,
    ) {}

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
            ->subject('Timesheet awaiting Project Director approval')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A timesheet has been approved by the Project Manager and now requires your sign-off.');

        foreach ($this->timesheetSummary($this->timesheet) as $label => $value) {
            $message->line("**{$label}:** {$value}");
        }

        if (filled($this->pmComment)) {
            $message->line('**PM comment:** '.$this->pmComment);
        }

        return $message
            ->action('Review timesheet', $this->timesheetViewUrl($this->timesheet))
            ->line('Please review and approve or reject this submission in Quatriz TimeSheet.');
    }
}
