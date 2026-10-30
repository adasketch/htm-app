<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Sample database seeding is restricted to local and testing environments.');
        }

        $configuredPassword = config('app.seed_user_password');
        if ($configuredPassword && strlen($configuredPassword) < 12) {
            throw new \RuntimeException('SEED_USER_PASSWORD must contain at least 12 characters.');
        }

        $this->call(PropertySeeder::class);

        $properties = Property::whereIn('name', [
            'The Westin Surabaya',
            'Four Points Surabaya Pakuwon Indah',
        ])->get();

        foreach ($properties as $property) {
            foreach (['Engineering', 'Housekeeping', 'Front Office'] as $departmentName) {
                Department::firstOrCreate([
                    'property_id' => $property->id,
                    'name' => $departmentName,
                ]);
            }
        }

        $property = $properties->firstWhere('name', 'The Westin Surabaya');
        $department = Department::where('property_id', $property->id)
            ->where('name', 'Engineering')
            ->firstOrFail();

        $password = $configuredPassword ?: Str::random(40);
        $admin = User::firstOrCreate(
            ['email' => 'admin@local.test'],
            [
                'name' => 'HotelTask Admin',
                'password' => $password,
                'property_id' => $property->id,
                'role' => 'Admin',
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ],
        );
        $this->setDefaultDepartment($admin, $department->id);

        $associate = User::firstOrCreate(
            ['email' => 'associate@local.test'],
            [
                'name' => 'Local Associate',
                'password' => $password,
                'property_id' => $property->id,
                'role' => 'Associate',
                'email_verified_at' => now(),
            ],
        );
        $this->setDefaultDepartment($associate, $department->id);

        if (app()->environment('local') && ! $configuredPassword) {
            $this->command?->warn('Local seed users were created with a random password. Set SEED_USER_PASSWORD in .env before seeding if you need a known local password.');
        }
    }

    private function setDefaultDepartment(User $user, string $departmentId): void
    {
        DB::transaction(function () use ($user, $departmentId): void {
            DB::table('department_user')->where('user_id', $user->id)->update([
                'is_default' => false,
                'updated_at' => now(),
            ]);

            $user->departments()->syncWithoutDetaching([$departmentId => ['is_default' => true]]);
        });
    }
}
