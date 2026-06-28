<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class TimesheetNotifier
{
    public static function enabled(): bool
    {
        return Setting::emailNotificationsEnabled();
    }

    public static function notifySubmitted(Timesheet $timesheet): void
    {
        if (! static::enabled()) {
            static::logSkipped('submitted', $timesheet, 'email_notifications_disabled');

            return;
        }

        $timesheet->loadMissing(['user', 'project.projectManager']);

        static::notifyRecipients(
            'submitted',
            $timesheet,
            static::submissionRecipients($timesheet),
            new \App\Notifications\TimesheetSubmittedNotification($timesheet),
        );
    }

    public static function notifyPendingDirector(Timesheet $timesheet, ?string $pmComment = null): void
    {
        if (! static::enabled()) {
            static::logSkipped('pending_director', $timesheet, 'email_notifications_disabled');

            return;
        }

        $timesheet->loadMissing(['user', 'project.projectDirector']);

        static::notifyRecipients(
            'pending_director',
            $timesheet,
            static::directorRecipients($timesheet),
            new \App\Notifications\TimesheetPendingDirectorNotification($timesheet, $pmComment),
        );
    }

    public static function notifyApproved(Timesheet $timesheet, User $approver, ?string $comment = null): void
    {
        if (! static::enabled()) {
            static::logSkipped('approved', $timesheet, 'email_notifications_disabled');

            return;
        }

        $employee = $timesheet->user;

        if (! $employee) {
            static::logSkipped('approved', $timesheet, 'missing_employee');

            return;
        }

        static::notifyRecipients(
            'approved',
            $timesheet,
            collect([$employee]),
            new \App\Notifications\TimesheetApprovedNotification($timesheet, $approver, $comment),
        );
    }

    public static function notifyRejected(Timesheet $timesheet, User $rejector, string $comment): void
    {
        if (! static::enabled()) {
            static::logSkipped('rejected', $timesheet, 'email_notifications_disabled');

            return;
        }

        $employee = $timesheet->user;

        if (! $employee) {
            static::logSkipped('rejected', $timesheet, 'missing_employee');

            return;
        }

        static::notifyRecipients(
            'rejected',
            $timesheet,
            collect([$employee]),
            new \App\Notifications\TimesheetRejectedNotification($timesheet, $rejector, $comment),
        );
    }

    /**
     * @return Collection<int, User>
     */
    protected static function submissionRecipients(Timesheet $timesheet): Collection
    {
        $manager = $timesheet->project?->projectManager;

        if ($manager) {
            return collect([$manager]);
        }

        return static::admins();
    }

    /**
     * @return Collection<int, User>
     */
    protected static function directorRecipients(Timesheet $timesheet): Collection
    {
        $director = $timesheet->project?->projectDirector;

        if ($director) {
            return collect([$director]);
        }

        return static::admins();
    }

    /**
     * @return Collection<int, User>
     */
    protected static function admins(): Collection
    {
        return User::query()->where('role', 'admin')->get();
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    protected static function notifyRecipients(
        string $event,
        Timesheet $timesheet,
        Collection $recipients,
        Notification $notification,
    ): void {
        $recipients = $recipients->filter()->unique('id');

        if ($recipients->isEmpty()) {
            static::logSkipped($event, $timesheet, 'no_recipients');

            return;
        }

        foreach ($recipients as $user) {
            try {
                if (config('timesheet.notifications.queue', true)) {
                    $user->notify($notification);
                } else {
                    NotificationFacade::sendNow($user, $notification);
                }

                Log::info('Timesheet notification dispatched', [
                    'event' => $event,
                    'timesheet_id' => $timesheet->id,
                    'recipient_id' => $user->id,
                    'recipient_email' => $user->email,
                    'queued' => config('timesheet.notifications.queue', true),
                ]);
            } catch (\Throwable $exception) {
                Log::error('Timesheet notification dispatch failed', [
                    'event' => $event,
                    'timesheet_id' => $timesheet->id,
                    'recipient_id' => $user->id,
                    'recipient_email' => $user->email,
                    'error' => $exception->getMessage(),
                ]);

                throw $exception;
            }
        }
    }

    protected static function logSkipped(string $event, Timesheet $timesheet, string $reason): void
    {
        Log::warning('Timesheet notification skipped', [
            'event' => $event,
            'timesheet_id' => $timesheet->id,
            'reason' => $reason,
        ]);
    }
}
