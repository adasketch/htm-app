<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $vendor = DB::table('roles')->where('key', 'Vendor')->first();
        $privileges = array_values(array_unique(array_merge(
            $vendor ? json_decode($vendor->privileges, true, flags: JSON_THROW_ON_ERROR) : [],
            ['tasks.update_assigned', 'tasks.comment', 'subtasks.manage_assigned'],
        )));

        DB::table('roles')->updateOrInsert(
            ['key' => 'Vendor'],
            [
                'id' => $vendor->id ?? (string) Str::uuid(),
                'name' => 'Vendor',
                'privileges' => json_encode($privileges, JSON_THROW_ON_ERROR),
                'is_system' => true,
                'created_at' => $vendor->created_at ?? now(),
                'updated_at' => now(),
            ],
        );

        Cache::forget('role_privileges:Vendor');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', 'Vendor')->update(['role' => 'Associate']);
        DB::table('roles')->where('key', 'Vendor')->delete();
        Cache::forget('role_privileges:Vendor');
    }
};
