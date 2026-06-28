<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'users_role_index');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('status', 'projects_status_index');
            $table->index('project_manager_id', 'projects_project_manager_id_index');
            $table->index('project_director_id', 'projects_project_director_id_index');
        });

        Schema::table('timesheets', function (Blueprint $table) {
            $table->index('project_id', 'timesheets_project_id_index');
            $table->index(['user_id', 'week_start'], 'timesheets_user_id_week_start_index');
            $table->index(['user_id', 'status'], 'timesheets_user_id_status_index');
            $table->index(['status', 'week_start'], 'timesheets_status_week_start_index');
        });

        Schema::table('approval_logs', function (Blueprint $table) {
            $table->dropIndex('approval_logs_timesheet_id_index');
            $table->index(
                ['timesheet_id', 'action', 'created_at'],
                'approval_logs_timesheet_action_created_index',
            );
            $table->index('user_id', 'approval_logs_user_id_index');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->index(['queue', 'available_at'], 'jobs_queue_available_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('jobs_queue_available_at_index');
        });

        Schema::table('approval_logs', function (Blueprint $table) {
            $table->dropIndex('approval_logs_user_id_index');
            $table->dropIndex('approval_logs_timesheet_action_created_index');
            $table->index('timesheet_id', 'approval_logs_timesheet_id_index');
        });

        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropIndex('timesheets_status_week_start_index');
            $table->dropIndex('timesheets_user_id_status_index');
            $table->dropIndex('timesheets_user_id_week_start_index');
            $table->dropIndex('timesheets_project_id_index');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_project_director_id_index');
            $table->dropIndex('projects_project_manager_id_index');
            $table->dropIndex('projects_status_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_index');
        });
    }
};
