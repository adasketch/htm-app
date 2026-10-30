<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('login'));

Route::get('/login', fn () => view('login'));
Route::get('/register', fn () => view('register'));
Route::get('/dashboard', function (Request $request) {
    return redirect($request->user()->role === 'Admin' ? '/dashboardadmin' : '/dashboardpublic');
})->middleware('page.access');
Route::get('/dashboardpublic', function (Request $request) {
    if ($request->user()->role === 'Admin') {
        return redirect('/dashboardadmin');
    }

    return view('dashboard', ['workspaceUser' => $request->user()]);
})->middleware('page.access');
Route::get('/tasks', function (Request $request) {
    if (in_array($request->user()->role, ['Admin', 'IT'], true)) {
        return redirect('/dashboardadmin/tasks');
    }

    return view('tasks');
})
    ->middleware('page.access:tasks.view_all,tasks.manage,tasks.assign,tasks.update_assigned');
Route::get('/reports', function (Request $request) {
    if (in_array($request->user()->role, ['Admin', 'IT'], true)) {
        return redirect('/dashboardadmin/reports');
    }

    return view('reports');
})->middleware('page.access:reports.view');
Route::get('/approvals', fn () => view('settings-users'))
    ->middleware('page.access:users.approve');

Route::prefix('dashboardadmin')->middleware(['page.access', 'admin.panel'])->group(function (): void {
    Route::get('/', fn () => redirect('/dashboardadmin/tasks'))
        ->middleware('page.access:tasks.view_all');
    Route::get('/tasks', fn () => view('tasks'))
        ->middleware('page.access:tasks.view_all,tasks.manage,tasks.assign,tasks.update_assigned');
    Route::get('/users', fn () => view('settings-users'))
        ->middleware('page.access:users.view');
    Route::get('/property-management', fn () => view('settings'))
        ->middleware('page.access:properties.manage,departments.manage');
    Route::get('/reports', fn () => view('reports'))
        ->middleware('page.access:reports.view');
});

Route::get('/task-tracker', fn () => redirect('/dashboardadmin/tasks#task-tracker'))
    ->middleware(['page.access:tasks.view_all', 'admin.panel']);
Route::get('/dashboardadmin/task-tracker', fn () => redirect('/dashboardadmin/tasks#task-tracker'))
    ->middleware(['page.access:tasks.view_all', 'admin.panel']);
Route::get('/settings', fn () => redirect('/dashboardadmin/property-management'))
    ->middleware(['page.access:properties.manage,departments.manage', 'admin.panel']);
Route::get('/settings/users', function (Request $request) {
    return redirect(in_array($request->user()->role, ['Admin', 'IT'], true)
        ? '/dashboardadmin/users'
        : '/approvals');
})->middleware('page.access:users.view');
