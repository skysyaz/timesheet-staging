<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->json('hours')->default('[0,0,0,0,0,0,0]');
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'project_id', 'week_start']);

            $table->index('status');
            $table->index('week_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};
