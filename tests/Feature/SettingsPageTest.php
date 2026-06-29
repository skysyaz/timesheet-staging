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

    public function test_admin_can_update_standard_weekly_hours(): void
    {
        Setting::create(['key' => 'standardWeeklyHours', 'value' => 40]);
        Setting::create(['key' => 'requireDirectorApproval', 'value' => true]);
        Setting::create(['key' => 'emailNotifications', 'value' => true]);

        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\Settings::class)
            ->fillForm([
                'standardWeeklyHours' => 37.5,
                'requireDirectorApproval' => false,
                'emailNotifications' => true,
            ])
            ->call('save')
            ->assertNotified();

        $this->assertSame(37.5, Setting::standardWeeklyHours());
        $this->assertFalse(Setting::getValue('requireDirectorApproval', true));
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
