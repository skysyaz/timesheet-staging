<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_cards_share_a_consistent_row_structure(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::create(['code' => 'AL-01', 'name' => 'Alignment Project']);
        Timesheet::create([
            'user_id' => $admin->id,
            'project_id' => $project->id,
            'week_start' => Carbon::now()->startOfWeek(Carbon::MONDAY),
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [2, 0, 0, 0, 0, 0, 0],
            'status' => 'approved',
        ]);

        $html = Livewire::actingAs($admin)
            ->test(Reports::class)
            ->html();

        // Every stat card must reserve the same 3-row structure (label / value /
        // caption-or-placeholder) so the big numbers land on a common baseline
        // across Entries, Total Hours, Weighted, and Average, even though only
        // Total Hours has a real "Reg X · OT Y" caption.
        $this->assertSame(4, substr_count($html, 'corp-stat-card'));
        $this->assertSame(3, substr_count($html, 'invisible text-xs" aria-hidden="true"'));

        // Numeric columns in the results table are right-aligned so the decimal
        // points/units stack under their headers; the label/distribution columns
        // stay left-aligned.
        $this->assertStringContainsString('text-right text-xs font-semibold uppercase', $html);
        $this->assertStringContainsString('text-left text-xs font-semibold uppercase', $html);
    }

    public function test_average_card_renders_decimal_formatted_zero_when_no_entries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $html = Livewire::actingAs($admin)
            ->test(Reports::class)
            ->set('dateFrom', '2000-01-01')
            ->set('dateTo', '2000-01-31')
            ->html();

        // Average must render as a decimal ("0.0h"), matching the Total Hours
        // and Weighted cards, instead of the old unformatted "0h" fallback.
        $this->assertStringContainsString('0.0<span class="ml-0.5 text-lg font-medium text-slate-400">h</span>', $html);
        $this->assertStringNotContainsString('0<span class="ml-0.5 text-lg font-medium text-slate-400">h</span>', str_replace('0.0<span', '', $html));
    }

    public function test_filtering_by_group_by_preserves_right_aligned_numeric_columns(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::create(['code' => 'AL-02', 'name' => 'Second Project']);
        Timesheet::create([
            'user_id' => $admin->id,
            'project_id' => $project->id,
            'week_start' => Carbon::now()->startOfWeek(Carbon::MONDAY),
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'approved',
        ]);

        $component = Livewire::actingAs($admin)
            ->test(Reports::class)
            ->set('reportType', 'member');

        $html = $component->html();

        $this->assertStringContainsString('text-right tabular-nums', $html);
        $this->assertStringContainsString('Member', $html);
    }

    public function test_totals_row_matches_column_alignment_of_data_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::create(['code' => 'AL-03', 'name' => 'Third Project']);
        Timesheet::create([
            'user_id' => $admin->id,
            'project_id' => $project->id,
            'week_start' => Carbon::now()->startOfWeek(Carbon::MONDAY),
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'approved',
        ]);

        $html = Livewire::actingAs($admin)
            ->test(Reports::class)
            ->html();

        $this->assertStringContainsString('text-right font-semibold tabular-nums', $html);
        $this->assertStringContainsString('40.0h', $html);
    }
}
