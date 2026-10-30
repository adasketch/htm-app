<?php

use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RolePrivilegeController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserDepartmentController;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public routes
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware([EncryptCookies::class, 'throttle:5,1']);
    Route::get('/registration-options', [RegistrationController::class, 'options'])->middleware('throttle:30,1');
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:5,1');

    // Protected Routes (Wajib Bearer Token Sanctum)
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Properties & Departments (Multi-Property Architecture)
        Route::get('/properties', [PropertyController::class, 'index']);
        Route::post('/properties', [PropertyController::class, 'store']);
        Route::patch('/properties/{propertyId}', [PropertyController::class, 'update']);
        Route::delete('/properties/{propertyId}', [PropertyController::class, 'destroy']);
        Route::get('/properties/{propertyId}/departments', [DepartmentController::class, 'index']);
        Route::post('/properties/{propertyId}/departments', [DepartmentController::class, 'store']);
        Route::delete('/properties/{propertyId}/departments/{departmentId}', [DepartmentController::class, 'destroy']);

        // Shortcut Departemen untuk properti yang sedang aktif
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::post('/departments', [DepartmentController::class, 'store']);

        // Users, Profile & Multi-Department Access
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/me', [UserController::class, 'me']);
        Route::patch('/users/me', [UserController::class, 'updateProfile']);
        Route::patch('/users/{id}', [UserController::class, 'updateManaged']);
        Route::post('/users/{id}/password', [UserController::class, 'setPassword']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        Route::post('/users/{id}/departments', [UserDepartmentController::class, 'addAccess']);
        Route::get('/users/pending-approvals', [ApprovalController::class, 'index']);
        Route::post('/users/{id}/approve', [ApprovalController::class, 'approve']);
        Route::post('/users/{id}/reject', [ApprovalController::class, 'reject']);

        Route::get('/roles', [RolePrivilegeController::class, 'index']);
        Route::put('/roles/{roleKey}/privileges', [RolePrivilegeController::class, 'update']);

        // Tasks, Operational Logs, Subtasks & Comments
        Route::get('/tasks/dashboard', [TaskController::class, 'dashboard']);
        Route::get('/tasks/assignees', [TaskController::class, 'assignees']);
        Route::get('/tasks', [TaskController::class, 'index']);
        Route::post('/tasks', [TaskController::class, 'store']);
        Route::post('/tasks/{id}/attachments', [TaskController::class, 'storeAttachments']);
        Route::get('/tasks/{id}/attachments/{attachmentId}', [TaskController::class, 'downloadAttachment'])
            ->name('api.v1.tasks.attachments.download');
        Route::get('/tasks/{id}', [TaskController::class, 'show']);
        Route::patch('/tasks/{id}/progress', [TaskController::class, 'updateProgress']);
        Route::patch('/tasks/{id}/due-date', [TaskController::class, 'extendDueDate']);
        Route::patch('/tasks/{id}/complete', [TaskController::class, 'complete']);
        Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);

        // Subtasks (Kendala)
        Route::post('/tasks/{id}/subtasks', [TaskController::class, 'addSubtask']);
        Route::patch('/tasks/{id}/subtasks/{subtaskId}/toggle', [TaskController::class, 'toggleSubtask']);
        Route::delete('/tasks/{id}/subtasks/{subtaskId}', [TaskController::class, 'destroySubtask']);
        Route::patch('/tasks/{id}/checklist-items/{itemId}/toggle', [TaskController::class, 'toggleChecklistItem']);

        // Task Comments (Updates Thread)
        Route::get('/tasks/{id}/comments', [TaskController::class, 'getComments']);
        Route::post('/tasks/{id}/comments', [TaskController::class, 'addComment']);
        Route::delete('/tasks/{id}/comments/{commentId}', [TaskController::class, 'destroyComment']);

        // Reports & Department SLA Performance
        Route::get('/reports/department-performance', [ReportController::class, 'departmentPerformance']);
    });
});
