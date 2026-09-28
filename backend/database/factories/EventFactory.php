<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'venue' => 'NEU Auditorium',
            'event_date' => today()->addDays(14)->toDateString(),
            'event_time' => '09:00:00',
            'budget' => 10000,
            'status' => 'upcoming',
            'created_by' => User::factory()->officer(),
        ];
    }

    public function inDays(int $days): static
    {
        return $this->state(fn () => [
            'event_date' => today()->addDays($days)->toDateString(),
            'status' => $days < 0 ? 'completed' : 'upcoming',
        ]);
    }
}
