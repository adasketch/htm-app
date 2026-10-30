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
        $associate = DB::table('roles')->where('key', 'Associate')->first();
        $privileges = $associate
            ? json_decode($associate->privileges, true, flags: JSON_THROW_ON_ERROR)
            : ['tasks.update_assigned', 'tasks.comment', 'subtasks.manage_assigned'];
        $now = now();

        foreach (['Daily Worker' => 'Daily Worker (DW)', 'Casual' => 'Casual'] as $key => $name) {
            $existingRole = DB::table('roles')->where('key', $key)->first();

            DB::table('roles')->updateOrInsert(
                ['key' => $key],
                [
                    'id' => $existingRole->id ?? (string) Str::uuid(),
                    'name' => $name,
                    'privileges' => json_encode($privileges, JSON_THROW_ON_ERROR),
                    'is_system' => true,
                    'created_at' => $existingRole->created_at ?? $now,
                    'updated_at' => $now,
                ],
            );

            Cache::forget('role_privileges:'.$key);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->whereIn('role', ['Daily Worker', 'Casual'])->update(['role' => 'Associate', 'updated_at' => now()]);
        DB::table('roles')->whereIn('key', ['Daily Worker', 'Casual'])->delete();
        Cache::forget('role_privileges:Daily Worker');
        Cache::forget('role_privileges:Casual');
    }
};
