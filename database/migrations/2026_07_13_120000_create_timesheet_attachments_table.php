<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timesheet_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timesheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index('timesheet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheet_attachments');
    }
};
