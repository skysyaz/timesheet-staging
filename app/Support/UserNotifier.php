<?php

namespace App\Support;

use App\Models\BroadcastEmail;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\UserActivationNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Password;

class UserNotifier
{
    public static function enabled(): bool
    {
        return Setting::emailNotificationsEnabled();
    }

    public static function sendActivation(User $user, ?string $plainPassword, ?string $subject = null, ?string $body = null): void
    {
        if (! static::enabled()) {
            static::logSkipped('activation', $user, 'email_notifications_disabled');

            return;
        }

        $notification = new UserActivationNotification(
            user: $user,
            plainPassword: $plainPassword,
            subject: $subject ?? 'Your Quatriz TimeSheet account is ready',
            body: $body ?? 'An account has been created for you. Sign in with the details below, or set your own password using the link.',
            activationToken: Password::createToken($user),
        );

        static::dispatch($user, $notification, 'activation');
    }

    /**
     * @param  Collection<int, User>  $users
     */
    public static function sendBroadcast(Collection $users, string $subject, string $body, ?User $sender = null): void
    {
        $users = $users->filter()->unique('id');

        if (! static::enabled()) {
            Log::warning('User broadcast skipped', [
                'reason' => 'email_notifications_disabled',
                'count' => $users->count(),
            ]);

            return;
        }

        $sent = 0;

        foreach ($users as $user) {
            static::dispatch($user, new UserActivationNotification(
                user: $user,
                plainPassword: null,
                subject: $subject,
                body: $body,
                activationToken: Password::createToken($user),
            ), 'broadcast');

            $sent++;
        }

        if ($sender !== null) {
            BroadcastEmail::create([
                'sender_id' => $sender->id,
                'subject' => $subject,
                'body' => $body,
                'recipient_count' => $sent,
            ]);
        }

        Log::info('User broadcast dispatched', ['count' => $sent]);
    }

    protected static function dispatch(User $user, UserActivationNotification $notification, string $event): void
    {
        try {
            if (config('timesheet.notifications.queue', true)) {
                $user->notify($notification);
            } else {
                NotificationFacade::sendNow($user, $notification);
            }

            Log::info('User notification dispatched', [
                'event' => $event,
                'recipient_id' => $user->id,
                'recipient_email' => $user->email,
                'queued' => config('timesheet.notifications.queue', true),
            ]);
        } catch (\Throwable $exception) {
            Log::error('User notification dispatch failed', [
                'event' => $event,
                'recipient_id' => $user->id,
                'recipient_email' => $user->email,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    protected static function logSkipped(string $event, User $user, string $reason): void
    {
        Log::warning('User notification skipped', [
            'event' => $event,
            'recipient_id' => $user->id,
            'reason' => $reason,
        ]);
    }
}
