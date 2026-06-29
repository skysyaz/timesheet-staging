<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timesheets', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'project_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table): void {
            $table->unique(['user_id', 'project_id', 'week_start']);
        });
    }
};
