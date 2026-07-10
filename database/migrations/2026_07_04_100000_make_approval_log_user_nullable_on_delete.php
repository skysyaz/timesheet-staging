<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preserve the historical approval trail when an approver's user account is
 * deleted. The original cascadeOnDelete wiped every approval_log row owned by
 * the deleted user, destroying the audit history of timesheets they approved.
 * nullOnDelete keeps the log row and simply nulls user_id instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('approval_logs', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->change();
        });

        Schema::table('approval_logs', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('approval_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('approval_logs', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('approval_logs', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};