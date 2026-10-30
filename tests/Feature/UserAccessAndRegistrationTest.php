<?php

namespace Tests\Feature;

use App\Mail\RegistrationDecisionMail;
use App\Mail\RegistrationPendingMail;
use App\Models\Department;
use App\Models\Property;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserAccessAndRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_promote_an_associate_and_non_admin_cannot_see_admin_accounts(): void
    {
        $property = Property::create(['name' => 'Test Property', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $admin = User::factory()->create(['role' => 'Admin', 'property_id' => $property->id]);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        $associate->departments()->attach($department->id, ['is_default' => true]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/users/{$associate->id}", [
            'name' => $associate->name,
            'email' => $associate->email,
            'position' => 'Operations Supervisor',
            'role' => 'Admin',
            'department_ids' => [$department->id],
            'default_department_id' => $department->id,
        ])->assertOk()->assertJsonPath('data.role', 'Admin')->assertJsonPath('data.position', 'Operations Supervisor');

        $secondAdmin = User::factory()->create(['role' => 'Admin', 'property_id' => $property->id]);
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonFragment(['email' => $admin->email])
            ->assertJsonMissing(['email' => $secondAdmin->email])
            ->assertJsonMissing(['email' => $associate->email]);

        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/users')->assertOk()->assertJsonMissing(['email' => $associate->email]);
    }

    public function test_admin_can_view_tasks_but_cannot_create_or_manage_them(): void
    {
        $property = Property::create(['name' => 'Test Property', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $admin = User::factory()->create(['role' => 'Admin', 'property_id' => $property->id]);
        $task = Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $admin->id,
            'title' => 'Existing task',
            'priority' => 'MEDIUM',
            'status' => 'NOT_STARTED',
            'progress_percentage' => 0,
        ]);

        Sanctum::actingAs($admin);

        $this->assertTrue($admin->hasPrivilege('tasks.view_all'));
        $this->assertFalse($admin->hasPrivilege('tasks.assign'));
        $this->assertFalse($admin->hasPrivilege('tasks.manage'));
        $this->getJson('/api/v1/tasks/dashboard')->assertOk()->assertJsonPath('statuses.created', 1);
        $this->postJson('/api/v1/tasks', [
            'property_id' => $property->id,
            'department_id' => $department->id,
            'title' => 'Unauthorized task',
            'priority' => 'MEDIUM',
        ])->assertForbidden();
        $this->deleteJson("/api/v1/tasks/{$task->id}")->assertForbidden();
    }

    public function test_task_visibility_and_management_are_limited_to_the_users_department_access(): void
    {
        $property = Property::create(['name' => 'Department Scope Property', 'timezone' => 'Asia/Jakarta']);
        $accessibleDepartment = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $otherDepartment = Department::create(['property_id' => $property->id, 'name' => 'Finance']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $manager->departments()->attach($accessibleDepartment->id, ['is_default' => true]);
        $accessibleTask = Task::create([
            'property_id' => $property->id,
            'department_id' => $accessibleDepartment->id,
            'created_by' => $manager->id,
            'title' => 'Operations task',
            'priority' => 'MEDIUM',
            'status' => 'NOT_STARTED',
            'progress_percentage' => 0,
        ]);
        $outsideTask = Task::create([
            'property_id' => $property->id,
            'department_id' => $otherDepartment->id,
            'created_by' => $manager->id,
            'title' => 'Finance task',
            'priority' => 'MEDIUM',
            'status' => 'NOT_STARTED',
            'progress_percentage' => 0,
        ]);
        $outsideAssignee = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        $outsideAssignee->departments()->attach($otherDepartment->id, ['is_default' => true]);
        $assignedOutsideTask = Task::create([
            'property_id' => $property->id,
            'department_id' => $otherDepartment->id,
            'created_by' => $outsideAssignee->id,
            'assigned_to' => $manager->id,
            'title' => 'Finance task assigned across departments',
            'priority' => 'MEDIUM',
            'status' => 'IN_PROGRESS',
            'progress_percentage' => 50,
        ]);

        Sanctum::actingAs($manager);

        $this->assertTrue($manager->hasPrivilege('tasks.view_all'));
        $this->getJson('/api/v1/tasks')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Operations task'])
            ->assertJsonMissing(['title' => 'Finance task'])
            ->assertJsonMissing(['title' => 'Finance task assigned across departments']);
        $this->getJson("/api/v1/tasks/{$outsideTask->id}")->assertNotFound();
        $this->getJson("/api/v1/tasks/{$assignedOutsideTask->id}")->assertNotFound();
        $this->getJson('/api/v1/tasks/dashboard')
            ->assertOk()
            ->assertJsonPath('statuses.created', 1)
            ->assertJsonPath('statuses.assigned', 0)
            ->assertJsonCount(1, 'tasks');
        $this->patchJson("/api/v1/tasks/{$outsideTask->id}/progress", [
            'progress_percentage' => 50,
        ])->assertNotFound();
        $this->deleteJson("/api/v1/tasks/{$outsideTask->id}")->assertNotFound();
        $this->assertDatabaseHas('tasks', [
            'id' => $outsideTask->id,
            'progress_percentage' => 0,
        ]);
        $this->assertModelExists($outsideTask);
        $this->deleteJson("/api/v1/tasks/{$accessibleTask->id}")->assertNoContent();
        $this->assertDatabaseMissing('tasks', ['id' => $accessibleTask->id]);
    }

    public function test_manager_can_create_tasks_only_in_a_department_they_can_access(): void
    {
        $property = Property::create(['name' => 'Task Creation Scope Property', 'timezone' => 'Asia/Jakarta']);
        $accessibleDepartment = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $otherDepartment = Department::create(['property_id' => $property->id, 'name' => 'Finance']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $manager->departments()->attach($accessibleDepartment->id, ['is_default' => true]);

        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/tasks', [
            'property_id' => $property->id,
            'department_id' => $otherDepartment->id,
            'title' => 'Out of scope task',
            'priority' => 'HIGH',
        ])->assertForbidden();
        $this->assertDatabaseMissing('tasks', ['title' => 'Out of scope task']);

        $this->postJson('/api/v1/tasks', [
            'property_id' => $property->id,
            'department_id' => $accessibleDepartment->id,
            'title' => 'In scope task',
            'priority' => 'HIGH',
        ])->assertCreated();
        $this->assertDatabaseHas('tasks', [
            'title' => 'In scope task',
            'department_id' => $accessibleDepartment->id,
        ]);
    }

    public function test_it_task_manager_can_see_unassigned_tasks_in_its_department_access(): void
    {
        $property = Property::create(['name' => 'IT Department Scope Property', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'IT']);
        $it = User::factory()->create(['role' => 'IT', 'property_id' => $property->id]);
        $it->departments()->attach($department->id, ['is_default' => true]);
        Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $it->id,
            'title' => 'Unassigned IT task',
            'priority' => 'MEDIUM',
            'status' => 'NOT_STARTED',
            'progress_percentage' => 0,
        ]);

        Sanctum::actingAs($it);

        $this->getJson('/api/v1/tasks')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Unassigned IT task']);
    }

    public function test_it_can_manage_users_and_tasks_but_cannot_manage_admin_accounts(): void
    {
        $property = Property::create(['name' => 'Test Property', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $it = User::factory()->create(['role' => 'IT', 'property_id' => $property->id]);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        $admin = User::factory()->create(['role' => 'Admin', 'property_id' => $property->id]);
        $associate->departments()->attach($department->id, ['is_default' => true]);
        $admin->departments()->attach($department->id, ['is_default' => true]);
        $it->departments()->attach($department->id, ['is_default' => true]);

        Sanctum::actingAs($it);

        $this->assertTrue($it->hasPrivilege('users.manage'));
        $this->assertTrue($it->hasPrivilege('tasks.assign'));
        $this->getJson('/api/v1/users')->assertOk()->assertJsonMissing(['email' => $admin->email]);
        $this->postJson('/api/v1/tasks', [
            'property_id' => $property->id,
            'department_id' => $department->id,
            'assigned_to' => $associate->id,
            'title' => 'IT created task',
            'priority' => 'HIGH',
        ])->assertCreated();
        $this->patchJson("/api/v1/users/{$associate->id}", [
            'role' => 'Department Leader',
        ])->assertOk()->assertJsonPath('data.role', 'Department Leader');
        $this->patchJson("/api/v1/users/{$admin->id}", [
            'role' => 'Associate',
        ])->assertNotFound();
    }

    public function test_admin_can_update_gm_privileges_and_property_details_but_cannot_delete_properties_with_data(): void
    {
        $property = Property::create(['name' => 'Old Hotel Name', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $admin = User::factory()->create(['role' => 'Admin', 'property_id' => $property->id]);
        $gm = User::factory()->create(['role' => 'GM', 'property_id' => $property->id]);

        Sanctum::actingAs($admin);

        $roles = $this->getJson('/api/v1/roles')->assertOk();
        $this->assertContains('GM', array_keys($roles->json('assignable_privileges')));
        $this->assertSame(
            $roles->json('assignable_privileges.Associate'),
            $roles->json('assignable_privileges.Daily Worker'),
        );
        $this->assertSame(
            $roles->json('assignable_privileges.Associate'),
            $roles->json('assignable_privileges.Casual'),
        );
        $this->assertSame(
            Role::where('key', 'Associate')->value('privileges'),
            Role::where('key', 'Daily Worker')->value('privileges'),
        );
        $this->assertSame(
            Role::where('key', 'Associate')->value('privileges'),
            Role::where('key', 'Casual')->value('privileges'),
        );
        $this->putJson('/api/v1/roles/GM/privileges', [
            'privileges' => ['properties.view_all', 'users.view', 'tasks.view_all'],
        ])->assertOk()->assertJsonPath('data.privileges', ['properties.view_all', 'users.view', 'tasks.view_all']);
        $this->putJson('/api/v1/roles/Associate/privileges', [
            'privileges' => [],
        ])->assertOk()->assertJsonPath('data.privileges', []);
        $this->assertSame([], Role::where('key', 'Associate')->value('privileges'));

        $this->patchJson("/api/v1/properties/{$property->id}", [
            'name' => 'New Hotel Name',
            'timezone' => 'Asia/Jakarta',
            'address' => 'Surabaya',
        ])->assertOk()->assertJsonPath('data.name', 'New Hotel Name');

        $this->deleteJson("/api/v1/properties/{$property->id}")->assertStatus(409);
        $this->assertDatabaseHas('users', ['id' => $gm->id, 'property_id' => $property->id]);
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_only_admin_can_delete_an_empty_property(): void
    {
        $property = Property::create(['name' => 'Empty Hotel', 'timezone' => 'Asia/Jakarta']);
        $admin = User::factory()->create(['role' => 'Admin']);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/properties/{$property->id}")->assertNoContent();
        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
    }

    public function test_manager_cannot_grant_department_access_to_their_own_account(): void
    {
        $property = Property::create(['name' => 'Test Property', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $manager->departments()->attach($department->id, ['is_default' => true]);

        Sanctum::actingAs($manager);

        $this->postJson("/api/v1/users/{$manager->id}/departments", [
            'department_id' => $department->id,
        ])->assertForbidden();
    }

    public function test_department_leader_only_sees_departments_they_lead(): void
    {
        $property = Property::create(['name' => 'Test Property', 'timezone' => 'Asia/Jakarta']);
        $managedDepartment = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        Department::create(['property_id' => $property->id, 'name' => 'Finance']);
        $leader = User::factory()->create(['role' => 'Department Leader', 'property_id' => $property->id]);
        $leader->departments()->attach($managedDepartment->id, ['is_default' => true]);

        Sanctum::actingAs($leader);

        $this->getJson("/api/v1/properties/{$property->id}/departments")
            ->assertOk()
            ->assertJsonFragment(['name' => 'Operations'])
            ->assertJsonMissing(['name' => 'Finance']);
    }

    public function test_only_approved_roles_can_approve_and_registration_sends_a_pending_notice(): void
    {
        $leaderPrivileges = Role::where('key', 'Department Leader')->value('privileges');
        $this->assertNotContains('users.approve', $leaderPrivileges);
        foreach (['Admin', 'IT', 'GM', 'Manager'] as $roleKey) {
            $this->assertContains('users.approve', Role::where('key', $roleKey)->value('privileges'));
        }

        Mail::fake();

        $property = Property::create(['name' => 'Approval Property', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);

        $this->getJson('/api/v1/registration-options')
            ->assertOk()
            ->assertJsonPath('data.0.id', $property->id)
            ->assertJsonPath('data.0.departments.0.id', $department->id);

        $otherProperty = Property::create(['name' => 'Other Property', 'timezone' => 'Asia/Jakarta']);
        $otherDepartment = Department::create(['property_id' => $otherProperty->id, 'name' => 'Finance']);
        $this->postJson('/api/v1/register', [
            'name' => 'Mismatched Applicant',
            'position' => 'Accountant',
            'email' => 'mismatch@example.test',
            'password' => 'a-secure-password',
            'password_confirmation' => 'a-secure-password',
            'property_id' => $property->id,
            'department_id' => $otherDepartment->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('department_id');

        $this->postJson('/api/v1/register', [
            'name' => 'New Associate',
            'position' => 'Front Office Associate',
            'email' => 'New.Associate@EXAMPLE.test',
            'password' => 'a-secure-password',
            'password_confirmation' => 'a-secure-password',
            'property_id' => $property->id,
            'department_id' => $department->id,
        ])->assertAccepted()
            ->assertJsonPath('approval_status', 'pending')
            ->assertJsonPath('email_notification_sent', true);

        Mail::assertSent(RegistrationPendingMail::class);
        $this->assertDatabaseHas('users', [
            'email' => 'new.associate@example.test',
            'position' => 'Front Office Associate',
            'property_id' => $property->id,
            'approval_status' => 'pending',
        ]);
        $this->assertDatabaseHas('department_user', [
            'user_id' => User::where('email', 'new.associate@example.test')->value('id'),
            'department_id' => $department->id,
            'is_default' => true,
        ]);
        $this->postJson('/api/v1/auth/login', [
            'email' => 'NEW.ASSOCIATE@example.test',
            'password' => 'a-secure-password',
        ])->assertForbidden()->assertJsonPath('approval_status', 'pending');
        $this->postJson('/api/v1/register', [
            'name' => 'Duplicate Associate',
            'email' => 'NEW.ASSOCIATE@example.test',
            'password' => 'another-secure-password',
            'password_confirmation' => 'another-secure-password',
        ])->assertUnprocessable();

        $leader = User::factory()->create(['role' => 'Department Leader', 'property_id' => $property->id]);
        $leader->departments()->attach($department->id, ['is_default' => true]);
        Sanctum::actingAs($leader);

        $pendingUser = User::where('email', 'new.associate@example.test')->firstOrFail();
        $this->getJson('/api/v1/users/pending-approvals')->assertForbidden();
        $this->postJson("/api/v1/users/{$pendingUser->id}/approve", [
            'property_id' => $property->id,
            'department_id' => $department->id,
            'role' => 'Associate',
        ])->assertForbidden();

        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $manager->departments()->attach($department->id, ['is_default' => true]);
        Sanctum::actingAs($manager);
        $this->postJson("/api/v1/users/{$pendingUser->id}/approve", [
            'property_id' => $property->id,
            'department_id' => $department->id,
            'role' => 'Associate',
        ])->assertOk()->assertJsonPath('email_notification_sent', true);

        Mail::assertSent(RegistrationDecisionMail::class);
        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'approval_status' => 'approved',
            'property_id' => $property->id,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'NEW.ASSOCIATE@example.test',
            'password' => 'a-secure-password',
        ])->assertOk()->assertJsonPath('user.approval_status', 'approved');
    }

    public function test_manager_can_only_view_or_reject_pending_registrations_in_their_property(): void
    {
        Mail::fake();

        $property = Property::create(['name' => 'Manager Property', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $otherProperty = Property::create(['name' => 'Other Manager Property', 'timezone' => 'Asia/Jakarta']);
        $otherDepartment = Department::create(['property_id' => $otherProperty->id, 'name' => 'Finance']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $manager->departments()->attach($department->id, ['is_default' => true]);
        $localApplicant = User::factory()->create([
            'role' => 'Associate',
            'approval_status' => 'pending',
            'property_id' => $property->id,
        ]);
        $localApplicant->departments()->attach($department->id, ['is_default' => true]);
        $remoteApplicant = User::factory()->create([
            'role' => 'Associate',
            'approval_status' => 'pending',
            'property_id' => $otherProperty->id,
        ]);
        $remoteApplicant->departments()->attach($otherDepartment->id, ['is_default' => true]);

        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/users/pending-approvals')
            ->assertOk()
            ->assertJsonFragment(['email' => $localApplicant->email])
            ->assertJsonMissing(['email' => $remoteApplicant->email]);
        $this->postJson("/api/v1/users/{$remoteApplicant->id}/reject")->assertNotFound();
        $this->assertDatabaseHas('users', [
            'id' => $remoteApplicant->id,
            'approval_status' => 'pending',
        ]);
        $this->postJson("/api/v1/users/{$localApplicant->id}/reject")->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $localApplicant->id,
            'approval_status' => 'rejected',
        ]);
    }

    public function test_admin_and_it_can_set_passwords_for_users_below_them_and_revoke_existing_tokens(): void
    {
        $property = Property::create(['name' => 'Password Property', 'timezone' => 'Asia/Jakarta']);
        $it = User::factory()->create(['role' => 'IT', 'property_id' => $property->id]);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        $token = $associate->createToken('existing-session')->accessToken;

        Sanctum::actingAs($it);

        $this->postJson("/api/v1/users/{$associate->id}/password", [
            'password' => 'A-secure-new-password-2026',
            'password_confirmation' => 'A-secure-new-password-2026',
        ])->assertOk();

        $this->assertTrue(Hash::check('A-secure-new-password-2026', $associate->fresh()->password));
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
        $this->postJson("/api/v1/users/{$associate->id}/password", [
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertUnprocessable();
    }

    public function test_it_can_soft_delete_a_user_without_deleting_their_task_history(): void
    {
        $property = Property::create(['name' => 'Deletion Property', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $it = User::factory()->create(['role' => 'IT', 'property_id' => $property->id]);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        $associate->departments()->attach($department->id, ['is_default' => true]);
        $task = Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $associate->id,
            'assigned_to' => $associate->id,
            'title' => 'Preserve task history',
            'priority' => 'MEDIUM',
            'status' => 'NOT_STARTED',
            'progress_percentage' => 0,
        ]);
        $comment = $task->comments()->create(['user_id' => $associate->id, 'comment' => 'Work update']);
        $subtask = $task->subtasks()->create(['created_by' => $associate->id, 'title' => 'Check equipment']);

        Sanctum::actingAs($it);

        $this->deleteJson("/api/v1/users/{$associate->id}")->assertNoContent();

        $this->assertSoftDeleted('users', ['id' => $associate->id]);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'created_by' => $associate->id]);
        $this->assertDatabaseHas('task_comments', ['id' => $comment->id, 'user_id' => $associate->id]);
        $this->assertDatabaseHas('subtasks', ['id' => $subtask->id, 'created_by' => $associate->id]);
        $this->assertDatabaseHas('users', ['id' => $associate->id, 'position' => null, 'property_id' => null]);
    }

    public function test_dashboard_analytics_are_aggregated_from_saved_tasks(): void
    {
        $property = Property::create(['name' => 'Test Property', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $admin = User::factory()->create(['role' => 'Admin', 'property_id' => $property->id]);

        foreach (['NOT_STARTED', 'IN_PROGRESS', 'DONE'] as $status) {
            Task::create([
                'property_id' => $property->id,
                'department_id' => $department->id,
                'created_by' => $admin->id,
                'title' => "{$status} task",
                'priority' => 'MEDIUM',
                'status' => $status,
                'progress_percentage' => match ($status) {
                    'IN_PROGRESS' => 25,
                    'DONE' => 100,
                    default => 0,
                },
            ]);
        }

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/tasks/dashboard?period=day')
            ->assertOk()
            ->assertJsonPath('period_counts.day', 3)
            ->assertJsonPath('statuses.created', 1)
            ->assertJsonPath('statuses.assigned', 1)
            ->assertJsonPath('statuses.done', 1)
            ->assertJsonPath('chart.data.13.total', 3)
            ->assertJsonPath('tasks.0.department.name', 'Operations');

        $weeklyResponse = $this->getJson('/api/v1/tasks/dashboard?period=week');
        $this->assertSame(3, $weeklyResponse->json('chart.data.11.total'), json_encode($weeklyResponse->json('chart')));
        $this->getJson('/api/v1/tasks/dashboard?period=month')->assertJsonPath('chart.data.11.total', 3);
        $this->getJson('/api/v1/tasks/dashboard?period=year')->assertJsonPath('chart.data.4.total', 3);
    }

    public function test_users_without_task_read_privileges_cannot_read_task_endpoints(): void
    {
        $property = Property::create(['name' => 'Read Access Property', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        $associate->departments()->attach($department->id, ['is_default' => true]);
        $task = Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $associate->id,
            'assigned_to' => $associate->id,
            'title' => 'Restricted task',
            'priority' => 'MEDIUM',
            'status' => 'NOT_STARTED',
            'progress_percentage' => 0,
        ]);
        Role::where('key', 'Associate')->firstOrFail()->update(['privileges' => []]);
        Cache::forget('role_privileges:Associate');
        Sanctum::actingAs($associate);

        $this->getJson('/api/v1/tasks')->assertForbidden();
        $this->getJson('/api/v1/tasks/dashboard')->assertForbidden();
        $this->getJson("/api/v1/tasks/{$task->id}")->assertForbidden();
        $this->getJson("/api/v1/tasks/{$task->id}/comments")->assertForbidden();
    }

    public function test_task_comments_subtasks_progress_and_department_filters_follow_privileges(): void
    {
        $property = Property::create(['name' => 'Task Workflow Property', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        foreach ([$manager, $associate] as $user) {
            $user->departments()->attach($department->id, ['is_default' => true]);
        }
        $task = Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $manager->id,
            'assigned_to' => $associate->id,
            'title' => 'Prepare meeting room',
            'priority' => 'HIGH',
            'status' => 'NOT_STARTED',
            'progress_percentage' => 0,
        ]);
        $task->subtasks()->create([
            'created_by' => $manager->id,
            'title' => 'Private creator identity',
        ]);

        Sanctum::actingAs($manager);
        $this->getJson("/api/v1/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('data.task_type', 'description')
            ->assertJsonPath('data.creator.email', $manager->email)
            ->assertJsonMissingPath('data.creator.privileges');
        $this->postJson("/api/v1/tasks/{$task->id}/subtasks", [
            'title' => 'Check projector',
        ])->assertForbidden();
        $this->patchJson("/api/v1/tasks/{$task->id}/subtasks/{$task->subtasks()->first()->id}/toggle")
            ->assertForbidden();
        $this->patchJson("/api/v1/tasks/{$task->id}/progress", ['progress_percentage' => 25])
            ->assertForbidden();
        $creatorComment = $this->postJson("/api/v1/tasks/{$task->id}/comments", ['comment' => 'Room is being prepared.'])
            ->assertCreated()->json('data');
        $this->getJson("/api/v1/tasks/{$task->id}/comments")
            ->assertOk()->assertJsonFragment(['comment' => 'Room is being prepared.']);
        $this->getJson('/api/v1/tasks?department_id='.$department->id.'&status=NOT_STARTED')
            ->assertOk()->assertJsonFragment(['title' => 'Prepare meeting room']);

        Sanctum::actingAs($associate);
        $this->getJson("/api/v1/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.creator.email')
            ->assertJsonMissingPath('data.creator.privileges')
            ->assertJsonMissingPath('data.assignee.email')
            ->assertJsonMissingPath('data.assignee.privileges')
            ->assertJsonMissingPath('data.subtasks.0.creator.email')
            ->assertJsonMissingPath('data.subtasks.0.creator.privileges');
        $this->patchJson("/api/v1/tasks/{$task->id}/progress", ['progress_percentage' => 45])
            ->assertOk()->assertJsonPath('data.status', 'IN_PROGRESS')->assertJsonPath('data.progress_percentage', 45);
        $this->patchJson("/api/v1/tasks/{$task->id}/progress", ['progress_percentage' => 100])
            ->assertOk()->assertJsonPath('data.status', 'IN_PROGRESS')->assertJsonPath('data.progress_percentage', 100);
        $this->patchJson("/api/v1/tasks/{$task->id}/subtasks/{$task->subtasks()->first()->id}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_completed', true);
        $this->patchJson("/api/v1/tasks/{$task->id}/complete")
            ->assertOk()->assertJsonPath('data.status', 'DONE');
        $subtask = $this->postJson("/api/v1/tasks/{$task->id}/subtasks", ['title' => 'Confirm table setup'])
            ->assertCreated()->json('data');
        $this->patchJson("/api/v1/tasks/{$task->id}/subtasks/{$subtask['id']}/toggle")
            ->assertOk()->assertJsonPath('data.is_completed', true);
        $comment = $this->postJson("/api/v1/tasks/{$task->id}/comments", ['comment' => 'Work started.'])
            ->assertCreated()->json('data');
        $this->deleteJson("/api/v1/tasks/{$task->id}/comments/{$creatorComment['id']}")->assertForbidden();
        $this->deleteJson("/api/v1/tasks/{$task->id}/comments/{$comment['id']}")->assertNoContent();
        $this->deleteJson("/api/v1/tasks/{$task->id}/subtasks/{$subtask['id']}")->assertNoContent();

        Sanctum::actingAs($manager);
        $this->deleteJson("/api/v1/tasks/{$task->id}/comments/{$creatorComment['id']}")->assertNoContent();
    }

    public function test_department_performance_report_is_limited_by_report_privilege_and_department_scope(): void
    {
        $property = Property::create(['name' => 'Reporting Property', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $otherDepartment = Department::create(['property_id' => $property->id, 'name' => 'Finance']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        $manager->departments()->attach($department->id, ['is_default' => true]);
        $associate->departments()->attach($department->id, ['is_default' => true]);
        Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $manager->id,
            'title' => 'Completed report task',
            'priority' => 'MEDIUM',
            'status' => 'DONE',
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);
        Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $manager->id,
            'assigned_to' => $associate->id,
            'title' => 'Unfinished report task',
            'priority' => 'MEDIUM',
            'status' => 'IN_PROGRESS',
            'progress_percentage' => 40,
        ]);

        Sanctum::actingAs($manager);
        $this->getJson('/api/v1/reports/department-performance?department_id='.$department->id.'&period=30', [
            'X-Property-ID' => $property->id,
        ])->assertOk()->assertJsonPath('metrics.total_tasks', 2)->assertJsonPath('metrics.done', 1);
        $this->getJson('/api/v1/reports/department-performance?department_id='.$department->id.'&period=30&task_status=completed', [
            'X-Property-ID' => $property->id,
        ])->assertOk()
            ->assertJsonCount(1, 'tasks.data')
            ->assertJsonFragment(['title' => 'Completed report task']);
        $this->getJson('/api/v1/reports/department-performance?department_id='.$department->id.'&period=30&task_status=unfinished', [
            'X-Property-ID' => $property->id,
        ])->assertOk()
            ->assertJsonCount(1, 'tasks.data')
            ->assertJsonFragment(['title' => 'Unfinished report task']);

        Sanctum::actingAs($associate);
        $this->getJson('/api/v1/reports/department-performance?department_id='.$department->id.'&period=30', [
            'X-Property-ID' => $property->id,
        ])->assertForbidden();

        Sanctum::actingAs($manager);
        $this->getJson('/api/v1/reports/department-performance?department_id='.$otherDepartment->id.'&period=30', [
            'X-Property-ID' => $property->id,
        ])->assertNotFound();
    }

    public function test_property_wide_privileges_do_not_expose_tasks_or_reports_outside_department_access(): void
    {
        $property = Property::create(['name' => 'GM Department Boundary', 'timezone' => 'Asia/Jakarta']);
        $accessibleDepartment = Department::create(['property_id' => $property->id, 'name' => 'Management']);
        $otherDepartment = Department::create(['property_id' => $property->id, 'name' => 'Engineering']);
        $gm = User::factory()->create(['role' => 'GM', 'property_id' => $property->id]);
        $gm->departments()->attach($accessibleDepartment->id, ['is_default' => true]);
        $task = Task::create([
            'property_id' => $property->id,
            'department_id' => $otherDepartment->id,
            'created_by' => $gm->id,
            'title' => 'Engineering task outside GM access',
            'priority' => 'MEDIUM',
            'status' => 'NOT_STARTED',
            'progress_percentage' => 0,
        ]);

        Sanctum::actingAs($gm);

        $this->getJson('/api/v1/tasks')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Engineering task outside GM access']);
        $this->getJson('/api/v1/tasks/dashboard?department_id='.$otherDepartment->id)->assertForbidden();
        $this->getJson('/api/v1/tasks/'.$task->id)->assertNotFound();
        $this->getJson('/api/v1/reports/department-performance?department_id='.$otherDepartment->id.'&period=30', [
            'X-Property-ID' => $property->id,
        ])->assertNotFound();
    }

    public function test_dashboard_recent_tasks_can_be_filtered_by_an_accessible_department_only(): void
    {
        $property = Property::create(['name' => 'Test Property', 'timezone' => 'Asia/Jakarta']);
        $otherProperty = Property::create(['name' => 'Other Property', 'timezone' => 'Asia/Jakarta']);
        $selectedDepartment = Department::create(['property_id' => $property->id, 'name' => 'Engineering']);
        $otherDepartment = Department::create(['property_id' => $property->id, 'name' => 'Housekeeping']);
        $outsideDepartment = Department::create(['property_id' => $otherProperty->id, 'name' => 'Finance']);
        $admin = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $admin->departments()->attach([
            $selectedDepartment->id => ['is_default' => true],
            $otherDepartment->id => ['is_default' => false],
        ]);

        foreach ([$selectedDepartment, $otherDepartment] as $department) {
            Task::create([
                'property_id' => $property->id,
                'department_id' => $department->id,
                'created_by' => $admin->id,
                'title' => $department->name.' task',
                'priority' => 'MEDIUM',
                'status' => 'NOT_STARTED',
                'progress_percentage' => 0,
            ]);
        }

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/tasks/dashboard?department_id='.$selectedDepartment->id)
            ->assertOk()
            ->assertJsonPath('statuses.created', 1)
            ->assertJsonPath('chart.data.13.total', 1)
            ->assertJsonCount(1, 'tasks')
            ->assertJsonPath('tasks.0.title', 'Engineering task');
        $this->getJson('/api/v1/tasks/dashboard?department_id='.$outsideDepartment->id)->assertForbidden();
    }

    public function test_dashboard_periods_and_chart_follow_the_requested_device_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-18 18:00:00', 'UTC'));
        try {
            $property = Property::create(['name' => 'Test Property', 'timezone' => 'Asia/Jakarta']);
            $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
            $admin = User::factory()->create(['role' => 'Admin', 'property_id' => $property->id]);

            foreach ([
                ['title' => 'Inside local day', 'created_at' => '2026-10-18 17:30:00'],
                ['title' => 'Before local day', 'created_at' => '2026-10-18 16:59:59'],
            ] as $taskData) {
                $task = Task::create([
                    'property_id' => $property->id,
                    'department_id' => $department->id,
                    'created_by' => $admin->id,
                    'title' => $taskData['title'],
                    'priority' => 'MEDIUM',
                    'status' => 'NOT_STARTED',
                    'progress_percentage' => 0,
                ]);
                DB::table('tasks')->where('id', $task->id)->update([
                    'created_at' => $taskData['created_at'],
                    'updated_at' => $taskData['created_at'],
                ]);
            }

            Sanctum::actingAs($admin);

            $dashboard = $this->getJson('/api/v1/tasks/dashboard?period=day&timezone=Asia%2FJakarta');
            $dashboard
                ->assertOk()
                ->assertJsonPath('timezone', 'Asia/Jakarta')
                ->assertJsonPath('period_counts.day', 1, json_encode($dashboard->json()))
                ->assertJsonPath('chart.data.13.label', '2026-10-19')
                ->assertJsonPath('chart.data.13.total', 1);

            $this->getJson('/api/v1/tasks/dashboard?timezone=Invalid%2FZone')->assertUnprocessable();
        } finally {
            Carbon::setTestNow();
        }
    }
}
