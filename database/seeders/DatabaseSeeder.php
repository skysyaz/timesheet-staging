<?php

namespace Database\Seeders;

use App\Models\ApprovalLog;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $thisMonday = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $lastMonday = (clone $thisMonday)->subWeek();
        $twoWeeksAgo = (clone $thisMonday)->subWeeks(2);

        $users = [
            ['name' => 'Ahmad', 'email' => 'ahmad@company.com', 'role' => 'employee', 'color' => '#0891b2'],
            ['name' => 'Sarah', 'email' => 'sarah@company.com', 'role' => 'project_manager', 'color' => '#d97706'],
            ['name' => 'David', 'email' => 'david@company.com', 'role' => 'project_director', 'color' => '#8b5cf6'],
            ['name' => 'John', 'email' => 'john@company.com', 'role' => 'employee', 'color' => '#7c3aed'],
            ['name' => 'Lisa', 'email' => 'lisa@company.com', 'role' => 'employee', 'color' => '#059669'],
            ['name' => 'Admin', 'email' => 'admin@company.com', 'role' => 'admin', 'color' => '#e11d48'],
        ];

        $createdUsers = [];
        foreach ($users as $u) {
            $createdUsers[] = User::create([
                'name' => $u['name'],
                'email' => $u['email'],
                'password' => 'pass123',
                'role' => $u['role'],
                'color' => $u['color'],
            ]);
        }

        $projects = [
            ['code' => 'ERP-01', 'name' => 'ERP System'],
            ['code' => 'MOB-01', 'name' => 'Mobile App'],
            ['code' => 'API-01', 'name' => 'API Project'],
            ['code' => 'WEB-01', 'name' => 'Website Redesign'],
            ['code' => 'CLD-01', 'name' => 'Cloud Migration'],
        ];

        $createdProjects = [];
        foreach ($projects as $p) {
            $createdProjects[] = Project::create($p);
        }

        $createdProjects[0]->update([
            'project_manager_id' => $createdUsers[1]->id,
            'project_director_id' => $createdUsers[2]->id,
        ]);
        $createdProjects[1]->update([
            'project_manager_id' => $createdUsers[1]->id,
            'project_director_id' => $createdUsers[2]->id,
        ]);
        $createdProjects[2]->update([
            'project_manager_id' => $createdUsers[1]->id,
            'project_director_id' => $createdUsers[2]->id,
        ]);
        $createdProjects[3]->update([
            'project_manager_id' => $createdUsers[1]->id,
            'project_director_id' => $createdUsers[2]->id,
        ]);
        $createdProjects[4]->update([
            'project_manager_id' => $createdUsers[1]->id,
            'project_director_id' => $createdUsers[2]->id,
        ]);

        Setting::create(['key' => 'requireDirectorApproval', 'value' => true]);
        Setting::create(['key' => 'emailNotifications', 'value' => true]);
        Setting::create(['key' => 'standardWeeklyHours', 'value' => 40]);

        $tsData = [
            ['user_id' => 1, 'project_id' => 1, 'week_start' => $twoWeeksAgo, 'hours' => [8,8,8,8,8,0,0], 'status' => 'approved', 'notes' => 'ERP module integration complete.'],
            ['user_id' => 4, 'project_id' => 2, 'week_start' => $twoWeeksAgo, 'hours' => [8,7,8,8,7,0,0], 'status' => 'approved', 'notes' => 'Mobile app sprint.'],
            ['user_id' => 1, 'project_id' => 1, 'week_start' => $lastMonday, 'hours' => [8,8,8,8,8,0,0], 'status' => 'approved', 'notes' => 'ERP reports module.'],
            ['user_id' => 4, 'project_id' => 2, 'week_start' => $lastMonday, 'hours' => [8,7,8,8,7,0,0], 'status' => 'pending_pd', 'notes' => 'Mobile app UI development sprint.'],
            ['user_id' => 1, 'project_id' => 3, 'week_start' => $lastMonday, 'hours' => [8,8,8,9,9,0,0], 'status' => 'pending_pm', 'notes' => 'API endpoints for payment gateway.'],
            ['user_id' => 5, 'project_id' => 2, 'week_start' => $lastMonday, 'hours' => [8,8,8,8,6,0,0], 'status' => 'pending_pm', 'notes' => 'Mobile app testing and QA.'],
            ['user_id' => 1, 'project_id' => 1, 'week_start' => $thisMonday, 'hours' => [8,8,0,0,0,0,0], 'status' => 'draft', 'notes' => 'Starting new module.'],
            ['user_id' => 4, 'project_id' => 3, 'week_start' => $thisMonday, 'hours' => [8,8,8,0,0,0,0], 'status' => 'rejected', 'notes' => 'API optimization work.'],
        ];

        $logData = [
            ['ts_idx' => 0, 'user_id' => 1, 'action' => 'submitted', 'comment' => '', 'offset' => 1],
            ['ts_idx' => 0, 'user_id' => 2, 'action' => 'approved_pm', 'comment' => 'Good', 'offset' => 2],
            ['ts_idx' => 0, 'user_id' => 3, 'action' => 'approved_pd', 'comment' => 'Final approved', 'offset' => 3],
            ['ts_idx' => 1, 'user_id' => 4, 'action' => 'submitted', 'comment' => '', 'offset' => 1],
            ['ts_idx' => 1, 'user_id' => 2, 'action' => 'approved_pm', 'comment' => 'Approved', 'offset' => 2],
            ['ts_idx' => 1, 'user_id' => 3, 'action' => 'approved_pd', 'comment' => '', 'offset' => 3],
            ['ts_idx' => 2, 'user_id' => 1, 'action' => 'submitted', 'comment' => '', 'offset' => 1],
            ['ts_idx' => 2, 'user_id' => 2, 'action' => 'approved_pm', 'comment' => '', 'offset' => 2],
            ['ts_idx' => 2, 'user_id' => 3, 'action' => 'approved_pd', 'comment' => 'Approved', 'offset' => 3],
            ['ts_idx' => 3, 'user_id' => 4, 'action' => 'submitted', 'comment' => '', 'offset' => 2],
            ['ts_idx' => 3, 'user_id' => 2, 'action' => 'approved_pm', 'comment' => 'PM approved', 'offset' => 3],
            ['ts_idx' => 4, 'user_id' => 1, 'action' => 'submitted', 'comment' => '', 'offset' => 4],
            ['ts_idx' => 5, 'user_id' => 5, 'action' => 'submitted', 'comment' => '', 'offset' => 3],
            ['ts_idx' => 7, 'user_id' => 4, 'action' => 'submitted', 'comment' => '', 'offset' => 0],
            ['ts_idx' => 7, 'user_id' => 2, 'action' => 'rejected_pm', 'comment' => 'Hours need re-distribution across the week.', 'offset' => 0],
        ];

        $tsIds = [];
        foreach ($tsData as $t) {
            $ts = Timesheet::create($t);
            $tsIds[] = $ts->id;
        }

        foreach ($logData as $log) {
            $ts = Timesheet::find($tsIds[$log['ts_idx']]);
            if (!$ts) continue;
            $ts->approvalLogs()->create([
                'user_id' => $log['user_id'],
                'action' => $log['action'],
                'comment' => $log['comment'],
                'created_at' => (clone $ts->week_start)->addDays($log['offset']),
                'updated_at' => (clone $ts->week_start)->addDays($log['offset']),
            ]);
        }
    }
}
