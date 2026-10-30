<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPageAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_must_authenticate_before_opening_dashboard_or_admin_pages(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/dashboardpublic')->assertRedirect('/login');
        $this->get('/dashboardadmin')->assertRedirect('/login');
        $this->get('/dashboardadmin/tasks')->assertRedirect('/login');
        $this->get('/dashboardadmin/task-tracker')->assertRedirect('/login');
        $this->get('/dashboardadmin/users')->assertRedirect('/login');
        $this->get('/dashboardadmin/property-management')->assertRedirect('/login');
        $this->get('/dashboardadmin/reports')->assertRedirect('/login');
        $this->get('/tasks')->assertRedirect('/login');
        $this->get('/reports')->assertRedirect('/login');
        $this->get('/settings')->assertRedirect('/login');
        $this->get('/settings/users')->assertRedirect('/login');
    }

    public function test_associate_can_open_dashboard_but_cannot_open_admin_pages(): void
    {
        $associate = User::factory()->create([
            'role' => 'Associate',
            'approval_status' => 'approved',
        ]);
        $token = $associate->createToken('page-test', ['*'], now()->addDay())->plainTextToken;

        $this->withCookie('hotel_task_page_token', $token)
            ->get('/dashboardpublic')
            ->assertOk()
            ->assertSee('id="sidebar-dashboard-link" href="/dashboardpublic"', false);
        $this->get('/dashboard')->assertRedirect('/dashboardpublic');
        $this->get('/dashboardadmin')->assertForbidden();
        $this->get('/dashboardadmin/tasks')->assertForbidden();
        $this->get('/dashboardadmin/task-tracker')->assertForbidden();
        $this->get('/dashboardadmin/users')->assertForbidden();
        $this->get('/dashboardadmin/property-management')->assertForbidden();
        $this->get('/dashboardadmin/reports')->assertForbidden();
        $this->get('/task-tracker')->assertForbidden();
        $this->get('/tasks')->assertOk()->assertDontSee('id="task-tracker"', false);
        $this->get('/dashboardpublic')
            ->assertOk()
            ->assertDontSee('id="app-sidebar"', false)
            ->assertDontSee('/dashboardadmin', false);
        $this->get('/reports')->assertForbidden();
        $this->get('/settings')->assertForbidden();
        $this->get('/settings/users')->assertForbidden();
    }

    public function test_frontline_roles_do_not_see_the_task_tracker(): void
    {
        foreach (['Associate', 'Vendor', 'Daily Worker', 'Casual'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'approval_status' => 'approved',
            ]);
            $token = $user->createToken('page-test', ['*'], now()->addDay())->plainTextToken;

            $this->withCookie('hotel_task_page_token', $token)
                ->get('/tasks')
                ->assertOk()
                ->assertDontSee('id="task-tracker"', false);
        }
    }

    public function test_admin_can_open_admin_pages_and_login_sets_http_only_page_cookie(): void
    {
        $admin = User::factory()->create([
            'email' => 'page-admin@example.test',
            'password' => 'a-secure-password',
            'role' => 'Admin',
            'approval_status' => 'approved',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'a-secure-password',
        ])->assertOk();

        $pageCookie = $loginResponse->getCookie('hotel_task_page_token');
        $this->assertNotNull($pageCookie);
        $this->assertTrue($pageCookie->isHttpOnly());
        $this->assertSame('lax', $pageCookie->getSameSite());

        $this->withCookie('hotel_task_page_token', $loginResponse->json('access_token'))
            ->get('/settings')
            ->assertRedirect('/dashboardadmin/property-management');
        $this->get('/dashboard')->assertRedirect('/dashboardadmin');
        $this->get('/dashboardpublic')->assertRedirect('/dashboardadmin');
        $this->get('/dashboardadmin')->assertRedirect('/dashboardadmin/tasks');
        $taskPage = $this->get('/dashboardadmin/tasks')->assertOk()
            ->assertDontSee('id="sidebar-dashboard-link"', false)
            ->assertSee('Task Tracker')
            ->assertSee('create-task-type', false)
            ->assertSee('task-progress-meter', false)
            ->assertSee('task-progress-controls', false)
            ->assertSee('task-progress-save', false)
            ->assertDontSee('task-mark-completed', false)
            ->assertSee('Mark as complete')
            ->assertSee('OVERDUE', false)
            ->assertSee('task-checklist-section', false)
            ->assertSee('id="create-assignee"', false)
            ->assertDontSee('Assign by role')
            ->assertSee('Delete comment', false)
            ->assertSee('Delete subtask', false)
            ->assertSee('id="create-attachments"', false)
            ->assertSee('id="task-attachment-form"', false)
            ->assertSee('task.tracker.changed')
            ->assertDontSee('setInterval(loadTaskTracker, 30000)', false)
            ->assertDontSee('sidebar-tracker-link', false);
        $this->assertLessThan(
            strpos($taskPage->getContent(), 'id="task-tracker"'),
            strpos($taskPage->getContent(), 'id="task-create-panel"'),
        );
        $this->get('/tasks')->assertRedirect('/dashboardadmin/tasks');
        $this->get('/dashboardadmin/task-tracker')->assertRedirect('/dashboardadmin/tasks#task-tracker');
        $this->get('/dashboardadmin/users')->assertOk();
        $this->get('/settings/users')->assertRedirect('/dashboardadmin/users');
        $this->get('/dashboardadmin/property-management')->assertOk();
        $this->get('/dashboardadmin/reports')->assertOk()->assertSee('report-task-status', false);
        $this->get('/reports')->assertRedirect('/dashboardadmin/reports');

        $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => 'Bearer '.$loginResponse->json('access_token'),
        ])->assertOk()->assertCookieExpired('hotel_task_page_token');
    }

    public function test_revoked_page_token_cannot_open_protected_pages(): void
    {
        $associate = User::factory()->create([
            'role' => 'Associate',
            'approval_status' => 'approved',
        ]);
        $token = $associate->createToken('page-test', ['*'], now()->addDay());
        $plainTextToken = $token->plainTextToken;
        $token->accessToken->delete();

        $this->withCookie('hotel_task_page_token', $plainTextToken)
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_it_users_land_on_the_isolated_workspace_and_can_enter_admin_area(): void
    {
        $itUser = User::factory()->create([
            'email' => 'it-page@example.test',
            'password' => 'a-secure-password',
            'name' => 'IT Operator',
            'role' => 'IT',
            'approval_status' => 'approved',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $itUser->email,
            'password' => 'a-secure-password',
        ])->assertOk();

        $this->withCookie('hotel_task_page_token', $loginResponse->json('access_token'))
            ->get('/dashboardpublic')
            ->assertOk()
            ->assertSee('Workspace')
            ->assertSee('id="workspace-it-admin-card"', false)
            ->assertDontSee('id="app-sidebar"', false)
            ->assertDontSee("user.role === 'IT'", false)
            ->assertDontSee('Task Tracker');
        $this->get('/dashboard')->assertRedirect('/dashboardpublic');
        $this->get('/dashboardadmin')->assertRedirect('/dashboardadmin/tasks');
        $this->get('/dashboardadmin/tasks')->assertOk()->assertSee('Task Tracker');
    }

    public function test_general_managers_cannot_open_admin_dashboard_or_property_management(): void
    {
        $manager = User::factory()->create([
            'role' => 'GM',
            'approval_status' => 'approved',
        ]);
        $token = $manager->createToken('page-test', ['*'], now()->addDay())->plainTextToken;

        $this->withCookie('hotel_task_page_token', $token)
            ->get('/dashboard')
            ->assertRedirect('/dashboardpublic');
        $this->get('/dashboardpublic')
            ->assertOk()
            ->assertDontSee('id="app-sidebar"', false)
            ->assertDontSee('id="workspace-it-admin-card"', false);
        $this->get('/dashboardadmin')->assertForbidden();
        $this->get('/tasks')->assertOk()->assertSee('id="task-tracker"', false);
        $this->get('/dashboardadmin/users')->assertForbidden();
        $this->get('/dashboardadmin/property-management')->assertForbidden();
        $this->get('/dashboardadmin/reports')->assertForbidden();
        $this->get('/settings')->assertForbidden();
        $this->get('/settings/users')->assertRedirect('/approvals');
        $this->get('/approvals')->assertOk()->assertSee('approvals-section');
    }
}
