<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminId = DB::table('users')->where('email', 'admin@local.test')->value('id');

        if (! $adminId) {
            return;
        }

        DB::table('tasks')
            ->where('created_by', $adminId)
            ->where('title', 'Sample maintenance task')
            ->where('description', 'Sample task for local development.')
            ->delete();
    }

    public function down(): void
    {
        // A removed sample task cannot be restored without recreating fake task data.
    }
};
