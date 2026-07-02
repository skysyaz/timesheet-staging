<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_project_director_id_index');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('project_director_id', 'program_manager_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('program_manager_id', 'projects_program_manager_id_index');
        });

        DB::table('users')
            ->where('role', 'project_director')
            ->update(['role' => 'program_manager']);

        DB::table('timesheets')
            ->where('status', 'pending_pd')
            ->update(['status' => 'pending_program_manager']);

        DB::table('approval_logs')
            ->where('action', 'approved_pd')
            ->update(['action' => 'approved_program_manager']);

        DB::table('approval_logs')
            ->where('action', 'rejected_pd')
            ->update(['action' => 'rejected_program_manager']);

        $directorApproval = DB::table('settings')
            ->where('key', 'requireDirectorApproval')
            ->first();

        if ($directorApproval) {
            DB::table('settings')
                ->where('key', 'requireDirectorApproval')
                ->update(['key' => 'requireProgramManagerApproval']);
        }
    }

    public function down(): void
    {
        $programManagerApproval = DB::table('settings')
            ->where('key', 'requireProgramManagerApproval')
            ->first();

        if ($programManagerApproval) {
            DB::table('settings')
                ->where('key', 'requireProgramManagerApproval')
                ->update(['key' => 'requireDirectorApproval']);
        }

        DB::table('approval_logs')
            ->where('action', 'approved_program_manager')
            ->update(['action' => 'approved_pd']);

        DB::table('approval_logs')
            ->where('action', 'rejected_program_manager')
            ->update(['action' => 'rejected_pd']);

        DB::table('timesheets')
            ->where('status', 'pending_program_manager')
            ->update(['status' => 'pending_pd']);

        DB::table('users')
            ->where('role', 'program_manager')
            ->update(['role' => 'project_director']);

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_program_manager_id_index');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('program_manager_id', 'project_director_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('project_director_id', 'projects_project_director_id_index');
        });
    }
};
