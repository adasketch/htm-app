<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateDepartmentLeaderPrivileges(false);
    }

    public function down(): void
    {
        $this->updateDepartmentLeaderPrivileges(true);
    }

    private function updateDepartmentLeaderPrivileges(bool $mayApproveRegistrations): void
    {
        $role = DB::table('roles')->where('key', 'Department Leader')->first();

        if (! $role) {
            return;
        }

        $privileges = json_decode($role->privileges, true, flags: JSON_THROW_ON_ERROR);
        $privileges = array_values(array_filter(
            $privileges,
            fn (string $privilege): bool => $privilege !== 'users.approve',
        ));

        if ($mayApproveRegistrations) {
            $privileges[] = 'users.approve';
        }

        DB::table('roles')->where('key', 'Department Leader')->update([
            'privileges' => json_encode(array_values(array_unique($privileges)), JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        Cache::forget('role_privileges:Department Leader');
    }
};
