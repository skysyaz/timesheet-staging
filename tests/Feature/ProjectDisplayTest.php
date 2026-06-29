<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Support\ProjectDisplay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_duplicate_project_names_in_database(): void
    {
        Project::create(['code' => 'A1', 'name' => 'Shared Name', 'status' => 'active']);
        Project::create(['code' => 'A2', 'name' => 'Shared Name', 'status' => 'active']);
        Project::create(['code' => 'B1', 'name' => 'Unique Name', 'status' => 'active']);

        $this->assertSame(['shared name'], ProjectDisplay::ambiguousNameKeys());
    }
}
