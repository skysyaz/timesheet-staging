<?php

namespace App\Notifications;

use App\Models\User;
use App\Support\Concerns\QueuesTimesheetNotification;
use Filament\Facades\Filament;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserActivationNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use QueuesTimesheetNotification;

    public function __construct(
        public User $user,
        public string $subject,
        public string $body,
        public ?string $activationToken = null,
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
            ->subject($this->subject)
            ->greeting('Hello '.$this->user->name.',')
            ->line($this->body);

        // No cleartext password: the set-password token below is the secure
        // onboarding path, so the temporary password is never mailed. The
        // token is only issued for users who haven't set their own password
        // yet; already-onboarded users receive a plain announcement.

        $loginUrl = Filament::getLoginUrl() ?: url('/login');

        $message->line('**Email:** '.$this->user->email);

        if ($this->activationToken !== null) {
            $message->action('Set your own password', route('password.set', [
                'token' => $this->activationToken,
                'email' => $this->user->email,
            ]));
        }

        return $message->line('Sign in at: '.$loginUrl);
    }
}
