<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Property;
use App\Models\Task;
use App\Models\TaskChecklistItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignee_must_complete_a_full_checklist_task_explicitly(): void
    {
        $property = Property::create(['name' => 'Checklist Hotel', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Housekeeping']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $associate = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id]);
        $manager->departments()->attach($department->id, ['is_default' => true]);
        $associate->departments()->attach($department->id, ['is_default' => true]);

        $this->actingAs($manager, 'sanctum');
        $createdTask = $this->postJson('/api/v1/tasks', [
            'property_id' => $property->id,
            'department_id' => $department->id,
            'assigned_to' => $associate->id,
            'title' => 'Prepare suite for arrival',
            'task_type' => 'checklist',
            'checklist_items' => ['Inspect the room', 'Restock amenities'],
            'priority' => 'HIGH',
        ])->assertCreated()
            ->assertJsonPath('data.task_type', 'checklist')
            ->assertJsonPath('data.assigned_to', $associate->id)
            ->assertJsonPath('data.progress_percentage', 0)
            ->json('data');

        $task = Task::findOrFail($createdTask['id']);
        $checklistItems = TaskChecklistItem::where('task_id', $task->id)->orderBy('position')->get();
        $subtask = $task->subtasks()->create([
            'created_by' => $associate->id,
            'title' => 'Confirm external dependency',
        ]);
        $this->assertCount(2, $checklistItems);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'progress_percentage' => 0, 'status' => 'NOT_STARTED']);

        $this->patchJson("/api/v1/tasks/{$task->id}/checklist-items/{$checklistItems[0]->id}/toggle")
            ->assertForbidden();

        $this->actingAs($associate, 'sanctum');
        $this->patchJson("/api/v1/tasks/{$task->id}/progress", ['progress_percentage' => 35])
            ->assertUnprocessable();

        $this->patchJson("/api/v1/tasks/{$task->id}/checklist-items/{$checklistItems[0]->id}/toggle")
            ->assertOk()
            ->assertJsonPath('data.progress_percentage', 50)
            ->assertJsonPath('data.status', 'IN_PROGRESS');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'progress_percentage' => 50, 'status' => 'IN_PROGRESS']);
        $this->patchJson("/api/v1/tasks/{$task->id}/complete")
            ->assertUnprocessable();

        $this->patchJson("/api/v1/tasks/{$task->id}/checklist-items/{$checklistItems[1]->id}/toggle")
            ->assertOk()
            ->assertJsonPath('data.progress_percentage', 100)
            ->assertJsonPath('data.status', 'IN_PROGRESS');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'progress_percentage' => 100, 'status' => 'IN_PROGRESS']);
        $this->getJson('/api/v1/tasks')->assertOk()->assertJsonPath('data.0.completion_ready', false);
        $this->patchJson("/api/v1/tasks/{$task->id}/complete")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Complete every subtask before marking the task as completed.');
        $this->patchJson("/api/v1/tasks/{$task->id}/subtasks/{$subtask->id}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_completed', true);
        $this->getJson('/api/v1/tasks')->assertOk()->assertJsonPath('data.0.completion_ready', true);

        $this->patchJson("/api/v1/tasks/{$task->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.progress_percentage', 100)
            ->assertJsonPath('data.status', 'DONE');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'progress_percentage' => 100, 'status' => 'DONE']);

        $this->patchJson("/api/v1/tasks/{$task->id}/checklist-items/{$checklistItems[0]->id}/toggle")
            ->assertOk()
            ->assertJsonPath('data.progress_percentage', 50)
            ->assertJsonPath('data.status', 'IN_PROGRESS');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'progress_percentage' => 50, 'status' => 'IN_PROGRESS']);
    }

    public function test_checklist_tasks_require_at_least_one_item(): void
    {
        $property = Property::create(['name' => 'Checklist Validation Hotel', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Engineering']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $manager->departments()->attach($department->id, ['is_default' => true]);

        $this->actingAs($manager, 'sanctum')
            ->postJson('/api/v1/tasks', [
                'property_id' => $property->id,
                'department_id' => $department->id,
                'title' => 'Checklist without items',
                'task_type' => 'checklist',
                'priority' => 'MEDIUM',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('tasks', ['title' => 'Checklist without items']);
    }

    public function test_task_index_filters_completed_and_overdue_tasks(): void
    {
        $property = Property::create(['name' => 'Task Filters Hotel', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Engineering']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $manager->departments()->attach($department->id, ['is_default' => true]);

        $taskAttributes = [
            'property_id' => $property->id,
            'department_id' => $department->id,
            'created_by' => $manager->id,
            'priority' => 'MEDIUM',
        ];
        Task::create($taskAttributes + [
            'title' => 'Overdue unfinished task',
            'status' => 'IN_PROGRESS',
            'progress_percentage' => 40,
            'due_date' => now()->subDay()->toDateString(),
        ]);
        Task::create($taskAttributes + [
            'title' => 'Completed task',
            'status' => 'DONE',
            'progress_percentage' => 100,
            'due_date' => now()->subDay()->toDateString(),
            'completed_at' => now(),
        ]);
        Task::create($taskAttributes + [
            'title' => 'Due today task',
            'status' => 'NOT_STARTED',
            'progress_percentage' => 0,
            'due_date' => today()->toDateString(),
        ]);

        $this->actingAs($manager, 'sanctum');
        $this->getJson('/api/v1/tasks?status=DONE')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Completed task']);
        $this->getJson('/api/v1/tasks?status=OVERDUE')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Overdue unfinished task']);
    }
}
