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
        public ?string $plainPassword,
        public string $subject,
        public string $body,
        public string $activationToken,
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

        if ($this->plainPassword !== null) {
            $message->line('**Email:** '.$this->user->email)
                ->line('**Temporary password:** '.$this->plainPassword);
        }

        $loginUrl = Filament::getLoginUrl() ?: url('/login');

        return $message
            ->action('Set your own password', route('password.set', [
                'token' => $this->activationToken,
                'email' => $this->user->email,
            ]))
            ->line('Sign in at: '.$loginUrl);
    }
}
