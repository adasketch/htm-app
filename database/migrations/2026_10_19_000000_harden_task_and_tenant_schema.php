<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $canonicalDepartments = [];
        foreach (DB::table('departments')->orderBy('id')->get() as $department) {
            $key = $department->property_id.':'.mb_strtolower(trim($department->name));

            if (! isset($canonicalDepartments[$key])) {
                $canonicalDepartments[$key] = $department->id;

                continue;
            }

            $canonicalId = $canonicalDepartments[$key];
            DB::table('tasks')->where('department_id', $department->id)
                ->update(['department_id' => $canonicalId]);

            foreach (DB::table('department_user')->where('department_id', $department->id)->get() as $membership) {
                $existing = DB::table('department_user')
                    ->where('user_id', $membership->user_id)
                    ->where('department_id', $canonicalId)
                    ->first();

                if ($existing) {
                    if ($membership->is_default && ! $existing->is_default) {
                        DB::table('department_user')->where('id', $existing->id)
                            ->update(['is_default' => true]);
                    }
                    DB::table('department_user')->where('id', $membership->id)->delete();
                } else {
                    DB::table('department_user')->where('id', $membership->id)
                        ->update(['department_id' => $canonicalId]);
                }
            }

            DB::table('departments')->where('id', $department->id)->delete();
        }

        $duplicateMemberships = DB::table('department_user')
            ->select('user_id', 'department_id', DB::raw('MIN(id) as keep_id'), DB::raw('MAX(is_default) as is_default'))
            ->groupBy('user_id', 'department_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateMemberships as $membership) {
            DB::table('department_user')->where('id', $membership->keep_id)
                ->update(['is_default' => (bool) $membership->is_default]);
            DB::table('department_user')->where('user_id', $membership->user_id)
                ->where('department_id', $membership->department_id)
                ->where('id', '<>', $membership->keep_id)
                ->delete();
        }

        $invalidMembershipIds = DB::table('department_user')
            ->join('users', 'users.id', '=', 'department_user.user_id')
            ->join('departments', 'departments.id', '=', 'department_user.department_id')
            ->where(function ($query) {
                $query->whereNull('users.property_id')
                    ->orWhereColumn('users.property_id', '<>', 'departments.property_id');
            })
            ->pluck('department_user.id');
        DB::table('department_user')->whereIn('id', $invalidMembershipIds)->delete();

        foreach (DB::table('users')->whereNotNull('property_id')->get() as $user) {
            $defaults = DB::table('department_user')
                ->where('user_id', $user->id)
                ->where('is_default', true)
                ->orderBy('id')
                ->get();

            if ($defaults->count() > 1) {
                DB::table('department_user')->whereIn('id', $defaults->skip(1)->pluck('id'))
                    ->update(['is_default' => false]);
            }

            $hasDefault = $defaults->isNotEmpty();

            if (! $hasDefault) {
                $defaultDepartment = DB::table('departments')
                    ->where('property_id', $user->property_id)
                    ->orderBy('id')
                    ->first();

                if ($defaultDepartment && ! DB::table('department_user')
                    ->where('user_id', $user->id)
                    ->where('department_id', $defaultDepartment->id)
                    ->exists()) {
                    DB::table('department_user')->insert([
                        'user_id' => $user->id,
                        'department_id' => $defaultDepartment->id,
                        'is_default' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } elseif ($defaultDepartment) {
                    DB::table('department_user')
                        ->where('user_id', $user->id)
                        ->where('department_id', $defaultDepartment->id)
                        ->update(['is_default' => true]);
                }
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('property_id')->references('id')->on('properties')->nullOnDelete();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->unique(['property_id', 'name']);
        });

        Schema::table('department_user', function (Blueprint $table) {
            $table->unique(['user_id', 'department_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->index();
            $table->index(['property_id', 'department_id', 'status', 'due_date']);
        });

        DB::table('tasks')->where('status', 'DONE')->whereNull('completed_at')
            ->update(['completed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['property_id', 'department_id', 'status', 'due_date']);
            $table->dropIndex(['completed_at']);
            $table->dropColumn('completed_at');
        });

        Schema::table('department_user', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'department_id']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique(['property_id', 'name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
        });
    }
};
