<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One-time backfill: the audit log's log_name was hardcoded to "admin" for
     * every entry, so the Audit Log page's "Log" badge always read "admin"
     * regardless of who acted. Relabel existing rows to the causer's role
     * (or "system" for console/seeder entries with no causer). New entries are
     * already written with the acting role by AuditLogger / LogsAuditableChanges.
     */
    public function up(): void
    {
        // Per-user UPDATE (portable across SQLite + Postgres; Postgres rejects
        // referencing the joined table in a JOIN-UPDATE's SET clause).
        foreach (DB::table('users')->select('id', 'role')->cursor() as $user) {
            DB::table('activity_log')
                ->where('causer_id', $user->id)
                ->update(['log_name' => $user->role]);
        }

        DB::table('activity_log')
            ->whereNull('causer_id')
            ->update(['log_name' => 'system']);
    }

    public function down(): void
    {
        // No meaningful reverse — the original "admin" label carried no role info.
    }
};
