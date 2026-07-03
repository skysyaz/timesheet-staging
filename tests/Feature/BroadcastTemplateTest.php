<?php

namespace Tests\Feature;

use App\Filament\Resources\BroadcastTemplateResource\Pages\ManageBroadcastTemplates;
use App\Models\BroadcastTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BroadcastTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_broadcast_template(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageBroadcastTemplates::class)
            ->callAction('create', [
                'name' => 'Welcome',
                'subject' => 'Your Quatriz TimeSheet account',
                'body' => 'Set your password using the link below.',
            ])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('broadcast_templates', [
            'name' => 'Welcome',
            'subject' => 'Your Quatriz TimeSheet account',
            'body' => 'Set your password using the link below.',
            'creator_id' => $admin->id,
        ]);
    }

    public function test_project_admin_can_manage_templates(): void
    {
        $projectAdmin = User::factory()->projectAdmin()->create();

        Livewire::actingAs($projectAdmin)
            ->test(ManageBroadcastTemplates::class)
            ->callAction('create', [
                'name' => 'Reminder',
                'subject' => 'Reminder',
                'body' => 'Please set your password.',
            ])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('broadcast_templates', ['name' => 'Reminder']);
    }

    public function test_employee_cannot_access_templates(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        Livewire::actingAs($employee)
            ->test(ManageBroadcastTemplates::class)
            ->assertForbidden();
    }

    public function test_templates_can_be_edited(): void
    {
        $admin = User::factory()->admin()->create();
        $template = BroadcastTemplate::create([
            'name' => 'Welcome',
            'subject' => 'Old subject',
            'body' => 'Old body',
            'creator_id' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ManageBroadcastTemplates::class)
            ->callTableAction('edit', $template->id, [
                'name' => 'Welcome',
                'subject' => 'New subject',
                'body' => 'New body',
            ])
            ->assertHasNoErrors();

        $this->assertSame('New subject', $template->fresh()->subject);
    }
}
