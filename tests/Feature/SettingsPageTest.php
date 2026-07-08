<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_standard_weekly_hours_and_approval_settings(): void
    {
        Setting::create(['key' => 'standardWeeklyHours', 'value' => 40]);
        Setting::create(['key' => 'requireProgramManagerApproval', 'value' => true]);
        Setting::create(['key' => 'emailNotifications', 'value' => true]);
        Setting::create(['key' => 'overtimeDailyThreshold', 'value' => 8]);
        Setting::create(['key' => 'overtimeRate', 'value' => 1.5]);

        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\Settings::class)
            ->fillForm([
                'standardWeeklyHours' => 37.5,
                'requireProgramManagerApproval' => false,
                'emailNotifications' => true,
                'overtimeDailyThreshold' => 8,
                'overtimeRate' => 2,
            ])
            ->call('save')
            ->assertNotified();

        $this->assertSame(37.5, Setting::standardWeeklyHours());
        $this->assertFalse(Setting::getValue('requireProgramManagerApproval', true));
        $this->assertFalse(Setting::programManagerApprovalRequired());
    }

    public function test_overtime_settings_are_readable_from_model(): void
    {
        Setting::setValue('overtimeDailyThreshold', 8);
        Setting::setValue('overtimeRate', 2.0);

        $this->assertSame(8.0, Setting::overtimeDailyThreshold());
        $this->assertSame(2.0, Setting::overtimeRate());
    }

    public function test_clearing_overtime_daily_threshold_removes_setting(): void
    {
        Setting::create(['key' => 'standardWeeklyHours', 'value' => 40]);
        Setting::create(['key' => 'requireProgramManagerApproval', 'value' => true]);
        Setting::create(['key' => 'emailNotifications', 'value' => true]);
        Setting::create(['key' => 'overtimeDailyThreshold', 'value' => 8]);
        Setting::create(['key' => 'overtimeRate', 'value' => 1.5]);

        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\Settings::class)
            ->fillForm([
                'standardWeeklyHours' => 40,
                'requireProgramManagerApproval' => true,
                'emailNotifications' => true,
                'overtimeDailyThreshold' => null,
                'overtimeRate' => 1.5,
            ])
            ->call('save')
            ->assertNotified();

        $this->assertNull(Setting::overtimeDailyThreshold());
        $this->assertDatabaseMissing('settings', ['key' => 'overtimeDailyThreshold']);
    }

    public function test_non_admin_cannot_access_settings_page(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)
            ->get('/settings')
            ->assertForbidden();
    }

    public function test_admin_can_reset_user_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'employee']);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
            ->callTableAction('resetPassword', $user, data: [
                'password' => 'NewPass123!',
                'password_confirmation' => 'NewPass123!',
            ])
            ->assertNotified();

        $user->refresh();

        $this->assertTrue(Hash::check('NewPass123!', $user->password));
    }
}
