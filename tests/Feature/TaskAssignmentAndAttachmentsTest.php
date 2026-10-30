<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Property;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskAssignmentAndAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_assignees_are_filtered_to_lower_roles_in_the_selected_department(): void
    {
        $property = Property::create(['name' => 'Assignment Hotel', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Engineering']);
        $otherDepartment = Department::create(['property_id' => $property->id, 'name' => 'Front Office']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $departmentLeader = User::factory()->create(['role' => 'Department Leader', 'property_id' => $property->id]);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        $vendor = User::factory()->create(['role' => 'Vendor', 'property_id' => $property->id]);
        $sameLevelManager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $higherLevelGm = User::factory()->create(['role' => 'GM', 'property_id' => $property->id]);
        $otherDepartmentAssociate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);

        foreach ([$manager, $departmentLeader, $associate, $vendor, $sameLevelManager, $higherLevelGm] as $user) {
            $user->departments()->attach($department->id, ['is_default' => true]);
        }
        $otherDepartmentAssociate->departments()->attach($otherDepartment->id, ['is_default' => true]);

        $this->actingAs($manager, 'sanctum');

        $this->getJson('/api/v1/tasks/assignees?department_id='.$department->id)
            ->assertOk()
            ->assertJsonMissingPath('roles')
            ->assertJsonFragment(['id' => $departmentLeader->id, 'role' => 'Department Leader'])
            ->assertJsonFragment(['id' => $associate->id, 'role' => 'Associate'])
            ->assertJsonFragment(['id' => $vendor->id, 'role' => 'Vendor'])
            ->assertJsonMissing(['id' => $sameLevelManager->id])
            ->assertJsonMissing(['id' => $higherLevelGm->id])
            ->assertJsonMissing(['id' => $otherDepartmentAssociate->id]);

        $this->postJson('/api/v1/tasks', [
            'property_id' => $property->id,
            'department_id' => $department->id,
            'assigned_to' => $departmentLeader->id,
            'title' => 'Inspect guest room wiring',
            'priority' => 'HIGH',
        ])->assertCreated()->assertJsonPath('data.assignee.id', $departmentLeader->id);

        $this->postJson('/api/v1/tasks', [
            'property_id' => $property->id,
            'department_id' => $department->id,
            'assigned_to' => $sameLevelManager->id,
            'title' => 'Task assigned to peer',
            'priority' => 'MEDIUM',
        ])->assertForbidden();

        $this->postJson('/api/v1/tasks', [
            'property_id' => $property->id,
            'department_id' => $department->id,
            'assigned_to' => $higherLevelGm->id,
            'title' => 'Task assigned upward',
            'priority' => 'MEDIUM',
        ])->assertForbidden();
    }

    public function test_gm_can_assign_across_properties_and_departments_and_assignee_can_access_task(): void
    {
        $westin = Property::create(['name' => 'The Westin', 'timezone' => 'Asia/Jakarta']);
        $fourPoints = Property::create(['name' => 'Four Points', 'timezone' => 'Asia/Jakarta']);
        $gmDepartment = Department::create(['property_id' => $westin->id, 'name' => 'Management']);
        $recipientDepartment = Department::create(['property_id' => $fourPoints->id, 'name' => 'Engineering']);
        $gm = User::factory()->create(['role' => 'GM', 'property_id' => $westin->id]);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $fourPoints->id]);
        $unrelatedDepartment = Department::create(['property_id' => $fourPoints->id, 'name' => 'Housekeeping']);
        $unrelatedAssociate = User::factory()->create(['role' => 'Associate', 'property_id' => $fourPoints->id]);
        $gm->departments()->attach($gmDepartment->id, ['is_default' => true]);
        $gm->departments()->attach($recipientDepartment->id, ['is_default' => false]);
        $associate->departments()->attach($recipientDepartment->id, ['is_default' => true]);
        $unrelatedAssociate->departments()->attach($unrelatedDepartment->id, ['is_default' => true]);

        $this->actingAs($gm, 'sanctum')
            ->getJson('/api/v1/properties')
            ->assertOk()
            ->assertJsonFragment(['id' => $westin->id])
            ->assertJsonFragment(['id' => $fourPoints->id]);

        $this->getJson('/api/v1/properties/'.$fourPoints->id.'/departments')
            ->assertOk()
            ->assertJsonFragment(['id' => $recipientDepartment->id]);

        $this->getJson('/api/v1/tasks/assignees?department_id='.$recipientDepartment->id)
            ->assertOk()
            ->assertJsonFragment(['id' => $associate->id, 'property_name' => 'Four Points'])
            ->assertJsonMissing(['id' => $unrelatedAssociate->id])
            ->assertJsonMissingPath('roles');

        $this->postJson('/api/v1/tasks', [
            'property_id' => $fourPoints->id,
            'department_id' => $recipientDepartment->id,
            'assigned_to' => $unrelatedAssociate->id,
            'title' => 'Assignment without department access',
            'priority' => 'HIGH',
        ])->assertNotFound();
        $this->assertDatabaseMissing('tasks', ['title' => 'Assignment without department access']);

        $this->postJson('/api/v1/tasks', [
            'property_id' => $fourPoints->id,
            'department_id' => $recipientDepartment->id,
            'assigned_to' => $gm->id,
            'title' => 'Self-assigned task',
            'priority' => 'HIGH',
        ])->assertForbidden();

        $taskResponse = $this->postJson('/api/v1/tasks', [
            'property_id' => $fourPoints->id,
            'department_id' => $recipientDepartment->id,
            'assigned_to' => $associate->id,
            'title' => 'Check room equipment',
            'priority' => 'HIGH',
        ])->assertCreated()->assertJsonPath('data.assignee.id', $associate->id);

        $taskId = $taskResponse->json('data.id');
        $this->actingAs($associate, 'sanctum')
            ->getJson('/api/v1/tasks/'.$taskId)
            ->assertOk()
            ->assertJsonPath('data.id', $taskId);
    }

    public function test_it_gm_manager_and_department_leader_can_assign_to_the_next_lower_tiers(): void
    {
        $property = Property::create(['name' => 'Role Ladder Hotel', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Operations']);
        $it = User::factory()->create(['role' => 'IT', 'property_id' => $property->id]);
        $gm = User::factory()->create(['role' => 'GM', 'property_id' => $property->id]);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $departmentLeader = User::factory()->create(['role' => 'Department Leader', 'property_id' => $property->id]);
        $vendor = User::factory()->create(['role' => 'Vendor', 'property_id' => $property->id]);

        foreach ([$it, $gm, $manager, $departmentLeader, $vendor] as $user) {
            $user->departments()->attach($department->id, ['is_default' => true]);
        }

        foreach ([
            [$it, $gm],
            [$gm, $manager],
            [$manager, $departmentLeader],
            [$departmentLeader, $vendor],
        ] as [$assigner, $assignee]) {
            $this->actingAs($assigner, 'sanctum')
                ->postJson('/api/v1/tasks', [
                    'property_id' => $property->id,
                    'department_id' => $department->id,
                    'assigned_to' => $assignee->id,
                    'title' => "{$assigner->role} task for {$assignee->role}",
                    'priority' => 'MEDIUM',
                ])
                ->assertCreated()
                ->assertJsonPath('data.assignee.id', $assignee->id);
        }
    }

    public function test_task_creator_can_upload_attachments_and_assignee_can_download_them(): void
    {
        Storage::fake('local');

        $property = Property::create(['name' => 'Attachment Hotel', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Engineering']);
        $otherDepartment = Department::create(['property_id' => $property->id, 'name' => 'Finance']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        $outsider = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);

        $manager->departments()->attach($department->id, ['is_default' => true]);
        $associate->departments()->attach($department->id, ['is_default' => true]);
        $outsider->departments()->attach($otherDepartment->id, ['is_default' => true]);

        $task = Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $manager->id,
            'assigned_to' => $associate->id,
            'title' => 'Repair bathroom fixture',
            'priority' => 'HIGH',
            'status' => 'NOT_STARTED',
            'progress_percentage' => 0,
        ]);

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/attachments", [
                'attachments' => [UploadedFile::fake()->image('creator-photo.png')],
            ])
            ->assertCreated()
            ->assertJsonFragment(['file_name' => 'creator-photo.png']);
        $creationTaskResponse = $this->post('/api/v1/tasks', [
            'property_id' => $property->id,
            'department_id' => $department->id,
            'title' => 'Create task with attachment',
            'priority' => 'MEDIUM',
            'attachments' => [UploadedFile::fake()->image('creation-photo.png')],
        ], ['Accept' => 'application/json'])->assertCreated();
        $creationTaskId = $creationTaskResponse->json('data.id');
        $creationAttachmentId = Task::findOrFail($creationTaskId)
            ->attachments()
            ->where('file_name', 'creation-photo.png')
            ->value('id');

        $this->actingAs($associate, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/attachments", [
                'attachments' => [
                    UploadedFile::fake()->image('assignee-photo.png'),
                ],
            ])->assertForbidden();

        $attachment = $task->attachments()->where('file_name', 'creator-photo.png')->firstOrFail();
        Storage::disk('local')->assertExists($attachment->storage_path);

        $this->getJson("/api/v1/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonFragment(['file_name' => 'creator-photo.png']);
        $this->get("/api/v1/tasks/{$task->id}/attachments/{$attachment->id}")->assertDownload('creator-photo.png');

        $this->actingAs($manager, 'sanctum');
        $this->get("/api/v1/tasks/{$task->id}/attachments/{$attachment->id}")->assertDownload('creator-photo.png');
        $this->get('/api/v1/tasks/'.$creationTaskId.'/attachments/'.$creationAttachmentId)
            ->assertDownload('creation-photo.png');

        $this->actingAs($outsider, 'sanctum');
        $this->get("/api/v1/tasks/{$task->id}/attachments/{$attachment->id}")->assertNotFound();
    }

    public function test_task_attachment_upload_rejects_unsupported_files_and_assignee_uploads(): void
    {
        Storage::fake('local');

        $property = Property::create(['name' => 'Secure Upload Hotel', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Engineering']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        $manager->departments()->attach($department->id, ['is_default' => true]);
        $associate->departments()->attach($department->id, ['is_default' => true]);

        $task = Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $manager->id,
            'assigned_to' => $associate->id,
            'title' => 'Inspect electrical panel',
            'priority' => 'MEDIUM',
            'status' => 'NOT_STARTED',
            'progress_percentage' => 0,
        ]);

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/attachments", [
                'attachments' => [UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream')],
            ])
            ->assertUnprocessable();

        $this->actingAs($associate, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/attachments", [
                'attachments' => [UploadedFile::fake()->image('assignee-photo.png')],
            ])
            ->assertForbidden();
        $this->assertDatabaseCount('task_attachments', 0);
    }

    public function test_completing_a_task_deletes_its_attachments_immediately(): void
    {
        Storage::fake('local');

        $property = Property::create(['name' => 'Retention Hotel', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Engineering']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        $manager->departments()->attach($department->id, ['is_default' => true]);
        $associate->departments()->attach($department->id, ['is_default' => true]);
        $task = Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $manager->id,
            'assigned_to' => $associate->id,
            'title' => 'Complete attachment retention task',
            'priority' => 'MEDIUM',
            'status' => 'IN_PROGRESS',
            'progress_percentage' => 100,
            'due_date' => '2026-11-05',
        ]);
        $path = "tasks/{$task->id}/attachments/complete.png";
        Storage::disk('local')->put($path, 'temporary attachment');
        $attachment = $task->attachments()->create([
            'uploaded_by' => $manager->id,
            'file_name' => 'complete.png',
            'storage_path' => $path,
            'mime_type' => 'image/png',
            'size_bytes' => 20,
        ]);

        $this->actingAs($associate, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'DONE');

        $this->assertDatabaseMissing('task_attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_scheduler_deletes_attachments_for_expired_and_completed_tasks_only(): void
    {
        Storage::fake('local');

        $this->travelTo(CarbonImmutable::parse('2026-10-30 17:00:00', 'UTC'));
        $property = Property::create(['name' => 'Scheduled Retention Hotel', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Engineering']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $manager->departments()->attach($department->id, ['is_default' => true]);
        $expiredTask = Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $manager->id,
            'title' => 'Expired task',
            'priority' => 'LOW',
            'status' => 'IN_PROGRESS',
            'progress_percentage' => 20,
            'due_date' => '2026-10-30',
        ]);
        $futureTask = Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $manager->id,
            'title' => 'Future task',
            'priority' => 'LOW',
            'status' => 'NOT_STARTED',
            'progress_percentage' => 0,
            'due_date' => '2026-10-31',
        ]);
        $completedTask = Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $manager->id,
            'title' => 'Completed task',
            'priority' => 'LOW',
            'status' => 'DONE',
            'progress_percentage' => 100,
            'due_date' => null,
        ]);
        $expiredPath = "tasks/{$expiredTask->id}/attachments/expired.pdf";
        $futurePath = "tasks/{$futureTask->id}/attachments/future.pdf";
        $completedPath = "tasks/{$completedTask->id}/attachments/completed.pdf";

        foreach ([[$expiredTask, $expiredPath], [$futureTask, $futurePath], [$completedTask, $completedPath]] as [$task, $path]) {
            Storage::disk('local')->put($path, 'temporary attachment');
            $task->attachments()->create([
                'uploaded_by' => $manager->id,
                'file_name' => basename($path),
                'storage_path' => $path,
                'mime_type' => 'application/pdf',
                'size_bytes' => 20,
            ]);
        }

        $this->artisan('tasks:purge-expired-attachments')->assertSuccessful();

        $this->assertDatabaseMissing('task_attachments', ['storage_path' => $expiredPath]);
        $this->assertDatabaseMissing('task_attachments', ['storage_path' => $completedPath]);
        $this->assertDatabaseHas('task_attachments', ['storage_path' => $futurePath]);
        Storage::disk('local')->assertMissing($expiredPath);
        Storage::disk('local')->assertMissing($completedPath);
        Storage::disk('local')->assertExists($futurePath);
    }

    public function test_task_assigner_can_only_extend_due_date_and_keeps_attachments(): void
    {
        Storage::fake('local');

        $this->travelTo(CarbonImmutable::parse('2026-10-30 08:00:00', 'UTC'));
        $property = Property::create(['name' => 'Due Date Extension Hotel', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Engineering']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        $manager->departments()->attach($department->id, ['is_default' => true]);
        $associate->departments()->attach($department->id, ['is_default' => true]);
        $task = Task::create([
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $manager->id,
            'assigned_to' => $associate->id,
            'title' => 'Task with an extendable due date',
            'priority' => 'MEDIUM',
            'status' => 'IN_PROGRESS',
            'progress_percentage' => 20,
            'due_date' => '2026-10-31',
        ]);
        $path = "tasks/{$task->id}/attachments/due-date.pdf";
        Storage::disk('local')->put($path, 'temporary attachment');
        $task->attachments()->create([
            'uploaded_by' => $manager->id,
            'file_name' => 'due-date.pdf',
            'storage_path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => 20,
        ]);

        $this->actingAs($manager, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}/due-date", ['due_date' => '2026-11-05'])
            ->assertOk()
            ->assertJsonPath('message', 'Task due date extended.');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'due_date' => '2026-11-05']);
        $this->artisan('tasks:purge-expired-attachments')->assertSuccessful();
        Storage::disk('local')->assertExists($path);

        $this->patchJson("/api/v1/tasks/{$task->id}/due-date", ['due_date' => '2026-11-04'])
            ->assertUnprocessable();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'due_date' => '2026-11-05']);

        $this->actingAs($associate, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}/due-date", ['due_date' => '2026-11-07'])
            ->assertForbidden();
        Storage::disk('local')->assertExists($path);
    }
}
