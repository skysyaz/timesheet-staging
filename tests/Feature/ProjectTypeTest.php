<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_seeds_default_project_types(): void
    {
        $this->assertDatabaseHas('project_types', [
            'slug' => 'product-development',
            'name' => 'Product Development',
        ]);

        $this->assertDatabaseHas('project_types', [
            'slug' => 'maintenance',
        ]);

        $this->assertNotNull(ProjectType::defaultId());
    }

    public function test_project_requires_type_on_create(): void
    {
        $typeId = ProjectType::defaultId();
        $this->assertNotNull($typeId);

        $project = Project::create([
            'code' => 'TYP-01',
            'name' => 'Typed Project',
            'project_type_id' => $typeId,
        ]);

        $this->assertSame($typeId, $project->fresh()->project_type_id);
        $this->assertNotNull($project->projectType);
    }

    public function test_active_options_lists_seeded_types(): void
    {
        $options = ProjectType::activeOptions();

        $this->assertArrayHasKey(ProjectType::defaultId(), $options);
        $this->assertContains('Product Development', $options);
    }
}
