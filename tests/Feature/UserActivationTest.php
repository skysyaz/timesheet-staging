<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\UserActivationNotification;
use App\Support\UserNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class UserActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_user_dispatches_activation_email_without_plaintext_password(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Ainie Idris',
                'email' => 'ainie@example.com',
                'role' => 'employee',
                'password' => 'secret-pass-123',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $user = User::query()->where('email', 'ainie@example.com')->firstOrFail();

        // The activation mail must carry a set-password token, never the
        // cleartext password (no plainPassword property exists anymore).
        Notification::assertSentTo(
            $user,
            UserActivationNotification::class,
            fn (UserActivationNotification $n) => filled($n->activationToken)
                && ! property_exists($n, 'plainPassword'),
        );
    }

    public function test_broadcast_action_sends_to_all_visible_users(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $users = User::factory()->count(3)->create(['role' => 'employee']);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callAction('broadcastEmail', [
                'subject' => 'Welcome to Quatriz TimeSheet',
                'body' => 'Please set your password using the link below.',
            ])
            ->assertHasNoErrors();

        foreach ($users as $user) {
            Notification::assertSentTo(
                $user,
                UserActivationNotification::class,
                fn (UserActivationNotification $n) => filled($n->activationToken),
            );
        }
    }

    public function test_broadcast_records_history_with_sender_and_recipient_count(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $employees = User::factory()->count(2)->create(['role' => 'employee']);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callAction('broadcastEmail', [
                'subject' => 'Welcome back',
                'body' => 'Set your password using the link below.',
            ])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('broadcast_emails', [
            'sender_id' => $admin->id,
            'subject' => 'Welcome back',
            'body' => 'Set your password using the link below.',
            'recipient_count' => 3, // admin sees all users: itself + 2 employees
        ]);

        foreach ($employees as $employee) {
            Notification::assertSentTo($employee, UserActivationNotification::class);
        }
    }

    public function test_notifications_are_skipped_when_email_disabled(): void
    {
        Notification::fake();
        Setting::setValue('emailNotifications', false);

        $user = User::factory()->unverified()->create();

        UserNotifier::sendActivation($user);

        Notification::assertNothingSent();
    }

    public function test_set_password_route_resets_password_and_marks_email_verified(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'ainie@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'ainie@example.com',
            'password' => 'new-secret-pass-123',
            'password_confirmation' => 'new-secret-pass-123',
        ]);

        $response->assertRedirect(route('filament.admin.auth.login'));

        $user = $user->fresh();
        $this->assertTrue(Hash::check('new-secret-pass-123', $user->password));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_set_password_rejects_invalid_token(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'ainie@example.com']);

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'ainie@example.com',
            'password' => 'new-secret-pass-123',
            'password_confirmation' => 'new-secret-pass-123',
        ])->assertSessionHasErrors('email');
    }
}
