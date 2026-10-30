<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('departments.{department}', function (User $user, Department $department): bool {
    return $user->approval_status === 'approved'
        && (
            $user->hasPrivilege('tasks.view_all')
            || $user->hasPrivilege('tasks.manage')
            || $user->hasPrivilege('tasks.assign')
            || $user->hasPrivilege('tasks.update_assigned')
        )
        && (
            $user->role === 'Admin'
            || $user->departments()->whereKey($department->id)->exists()
        );
});

Broadcast::channel('users.{userId}', function (User $user, string $userId): bool {
    return $user->id === $userId
        && $user->approval_status === 'approved'
        && (
            $user->hasPrivilege('tasks.view_all')
            || $user->hasPrivilege('tasks.manage')
            || $user->hasPrivilege('tasks.assign')
            || $user->hasPrivilege('tasks.update_assigned')
        );
});
