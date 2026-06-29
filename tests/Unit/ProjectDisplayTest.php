<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Timesheet;
use App\Support\ProjectDisplay;
use Tests\TestCase;

class ProjectDisplayTest extends TestCase
{
    public function test_shows_project_name_when_available(): void
    {
        $project = new Project([
            'code' => 'PRJ-001',
            'name' => 'Sky Tower',
            'status' => 'active',
        ]);

        $this->assertSame('Sky Tower', ProjectDisplay::listLabel($project, []));
    }

    public function test_disambiguates_duplicate_names_with_code(): void
    {
        $project = new Project([
            'code' => 'PRJ-002',
            'name' => 'Sky Tower',
            'status' => 'active',
        ]);

        $this->assertSame(
            'Sky Tower (PRJ-002)',
            ProjectDisplay::listLabel($project, ['sky tower']),
        );
    }

    public function test_falls_back_to_code_when_name_missing(): void
    {
        $project = new Project([
            'code' => 'PRJ-003',
            'name' => '',
            'status' => 'active',
        ]);

        $this->assertSame('PRJ-003 (Name unavailable)', ProjectDisplay::listLabel($project, []));
    }

    public function test_shows_placeholder_when_project_record_missing(): void
    {
        $timesheet = new Timesheet(['project_id' => 99]);

        $this->assertSame(
            'Project unavailable (Name unavailable)',
            ProjectDisplay::listLabelForTimesheet($timesheet, []),
        );
    }

    public function test_appends_status_for_non_active_projects(): void
    {
        $project = new Project([
            'code' => 'PRJ-004',
            'name' => 'Legacy Site',
            'status' => 'archived',
        ]);

        $this->assertSame('Legacy Site (Archived)', ProjectDisplay::listLabel($project, []));
    }

    public function test_disambiguates_and_appends_status_when_needed(): void
    {
        $project = new Project([
            'code' => 'PRJ-005',
            'name' => 'Legacy Site',
            'status' => 'inactive',
        ]);

        $this->assertSame(
            'Legacy Site (PRJ-005) (Inactive)',
            ProjectDisplay::listLabel($project, ['legacy site']),
        );
    }
}
