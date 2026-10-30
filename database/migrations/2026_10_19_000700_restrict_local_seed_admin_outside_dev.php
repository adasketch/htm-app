<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment(['local', 'testing'])) {
            return;
        }

        DB::table('users')
            ->where('email', 'admin@local.test')
            ->where('role', 'Admin')
            ->update(['role' => 'Associate', 'updated_at' => now()]);

        Cache::forget('role_privileges:Admin');
        Cache::forget('role_privileges:Associate');
    }

    public function down(): void
    {
        // Never restore elevated access to a reserved local-development account.
    }
};
