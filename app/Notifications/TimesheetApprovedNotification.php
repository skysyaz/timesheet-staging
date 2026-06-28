<?php

namespace App\Notifications;

use App\Models\Timesheet;
use App\Models\User;
use App\Support\Concerns\BuildsTimesheetMail;
use App\Support\Concerns\QueuesTimesheetNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimesheetApprovedNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use BuildsTimesheetMail;
    use QueuesTimesheetNotification;

    public function __construct(
        public Timesheet $timesheet,
        public User $approver,
        public ?string $comment = null,
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
        $message = (new MailMessage)
            ->subject('Your timesheet has been approved')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your timesheet has been fully approved by '.$this->approver->name.'.');

        foreach ($this->timesheetSummary($this->timesheet) as $label => $value) {
            $message->line("**{$label}:** {$value}");
        }

        if (filled($this->comment)) {
            $message->line('**Approver comment:** '.$this->comment);
        }

        return $message
            ->action('View timesheet', $this->timesheetViewUrl($this->timesheet))
            ->line('No further action is required.');
    }
}
