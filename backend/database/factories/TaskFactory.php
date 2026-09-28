<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->sentence(3),
            'due_date' => today()->addDays(7)->toDateString(),
            'status' => 'pending',
            'priority' => 'medium',
            'assigned_to' => null,
        ];
    }

    public function done(): static
    {
        return $this->state(fn () => ['status' => 'done']);
    }
}
