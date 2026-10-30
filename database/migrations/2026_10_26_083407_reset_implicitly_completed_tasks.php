<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tasks')
            ->where('status', 'DONE')
            ->where('progress_percentage', 100)
            ->update([
                'status' => 'IN_PROGRESS',
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Completion state cannot be safely reconstructed without the assignee's confirmation.
    }
};
