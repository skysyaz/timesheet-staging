<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservabilityAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_audit_log_page(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->get('/admin/activity-logs')
            ->assertOk();
    }

    public function test_non_admin_cannot_open_audit_log_page(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $this->actingAs($employee);

        $this->get('/admin/activity-logs')
            ->assertForbidden();
    }
}
