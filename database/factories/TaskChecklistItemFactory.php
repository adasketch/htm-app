<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskChecklistItem>
 */
class TaskChecklistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'title' => fake()->sentence(4),
            'is_completed' => false,
            'position' => 0,
        ];
    }
}
