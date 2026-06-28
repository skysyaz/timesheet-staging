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

class TimesheetRejectedNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use BuildsTimesheetMail;
    use QueuesTimesheetNotification;

    public function __construct(
        public Timesheet $timesheet,
        public User $rejector,
        public string $comment,
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
            ->subject('Your timesheet was rejected')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your timesheet was rejected by '.$this->rejector->name.'. Please update and resubmit.');

        foreach ($this->timesheetSummary($this->timesheet) as $label => $value) {
            $message->line("**{$label}:** {$value}");
        }

        return $message
            ->line('**Reason:** '.$this->comment)
            ->action('Edit timesheet', route('filament.admin.resources.timesheets.edit', ['record' => $this->timesheet]))
            ->line('Make the requested changes and submit again when ready.');
    }
}
