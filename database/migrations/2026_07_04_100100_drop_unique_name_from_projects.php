<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `unique('name')` constraint added in 2026_06_29 conflicts with the
 * project-disambiguation design (see App\Support\ProjectDisplay), which
 * deliberately supports duplicate project names distinguished by code, and
 * with reusing a soft-deleted project's name. Drop the constraint so the
 * design works as intended; the disambiguation logic handles display.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unique('name');
        });
    }
};