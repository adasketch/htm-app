<?php

namespace Tests\Feature;

use App\Events\TaskTrackerChanged;
use App\Models\Department;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TaskTrackerBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_task_dispatches_a_private_department_tracker_update(): void
    {
        $property = Property::create(['name' => 'Realtime Hotel', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Engineering']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id]);
        $manager->departments()->attach($department->id, ['is_default' => true]);
        Event::fake([TaskTrackerChanged::class]);

        $this->actingAs($manager, 'sanctum')
            ->postJson('/api/v1/tasks', [
                'property_id' => $property->id,
                'department_id' => $department->id,
                'title' => 'Inspect water pump',
                'priority' => 'HIGH',
            ])
            ->assertCreated();

        Event::assertDispatched(TaskTrackerChanged::class, function (TaskTrackerChanged $event) use ($department): bool {
            return $event->departmentId === $department->id
                && $event->broadcastAs() === 'task.tracker.changed'
                && $event->broadcastWith() === ['department_id' => $department->id]
                && $event->broadcastOn()->name === 'private-departments.'.$department->id;
        });
    }

    public function test_only_approved_task_users_in_a_department_can_subscribe_to_its_tracker_updates(): void
    {
        $property = Property::create(['name' => 'Private Tracker Hotel', 'timezone' => 'Asia/Jakarta']);
        $department = Department::create(['property_id' => $property->id, 'name' => 'Engineering']);
        $otherDepartment = Department::create(['property_id' => $property->id, 'name' => 'Finance']);
        $manager = User::factory()->create(['role' => 'Manager', 'property_id' => $property->id, 'approval_status' => 'approved']);
        $outsider = User::factory()->create(['role' => 'Associate', 'property_id' => $property->id, 'approval_status' => 'approved']);
        $manager->departments()->attach($department->id, ['is_default' => true]);
        $outsider->departments()->attach($otherDepartment->id, ['is_default' => true]);
        $channel = 'private-departments.'.$department->id;
        config(['broadcasting.default' => 'reverb']);
        require base_path('routes/channels.php');
        $channelCallback = Broadcast::getFacadeRoot()->getChannels()->get('departments.{department}');
        $this->assertNotNull($channelCallback);
        $this->assertTrue($channelCallback($manager, $department));

        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => $channel,
        ])->assertUnauthorized();

        $this->actingAs($manager, 'sanctum')
            ->postJson('/api/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => $channel,
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);

        $this->actingAs($outsider, 'sanctum')
            ->postJson('/api/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => $channel,
            ])
            ->assertForbidden();
    }
}
