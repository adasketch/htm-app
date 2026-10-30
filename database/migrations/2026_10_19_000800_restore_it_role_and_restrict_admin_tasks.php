<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const IT_PRIVILEGES = [
        'properties.view_all', 'properties.manage', 'departments.manage',
        'users.view', 'users.view_all', 'users.approve', 'users.manage',
        'users.manage_departments', 'tasks.assign', 'tasks.manage',
        'tasks.view_all', 'tasks.update_assigned', 'tasks.comment',
        'subtasks.manage_assigned', 'reports.view',
    ];

    private const ADMIN_PRIVILEGES = [
        'properties.view_all', 'properties.manage', 'departments.manage',
        'users.view', 'users.view_all', 'users.approve', 'users.assign_roles',
        'users.manage', 'users.manage_departments', 'roles.manage',
        'tasks.view_all', 'reports.view',
    ];

    public function up(): void
    {
        $now = now();
        DB::table('roles')->updateOrInsert(
            ['key' => 'IT'],
            [
                'id' => DB::table('roles')->where('key', 'IT')->value('id') ?? (string) Str::uuid(),
                'name' => 'IT',
                'privileges' => json_encode(self::IT_PRIVILEGES, JSON_THROW_ON_ERROR),
                'is_system' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        DB::table('roles')->where('key', 'Admin')->update([
            'privileges' => json_encode(self::ADMIN_PRIVILEGES, JSON_THROW_ON_ERROR),
            'updated_at' => $now,
        ]);

        foreach (['Admin', 'IT'] as $roleKey) {
            Cache::forget('role_privileges:'.$roleKey);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'IT')->update(['role' => 'GM']);
        DB::table('roles')->where('key', 'IT')->delete();

        DB::table('roles')->where('key', 'Admin')->update([
            'privileges' => json_encode(array_merge(self::ADMIN_PRIVILEGES, [
                'tasks.assign', 'tasks.manage', 'tasks.update_assigned',
                'tasks.comment', 'subtasks.manage_assigned',
            ]), JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        foreach (['Admin', 'IT', 'GM'] as $roleKey) {
            Cache::forget('role_privileges:'.$roleKey);
        }
    }
};
