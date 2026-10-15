<?php

namespace Database\Factories;

use App\Models\TrackingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackingEvent>
 */
class TrackingEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_name' => 'Purchase',
            'event_id' => 'purchase-'.fake()->unique()->numberBetween(1, 999999),
            'reference' => null,
            'value' => null,
            'status' => 'sent',
            'events_received' => 1,
            'fbtrace_id' => fake()->bothify('??########'),
            'error' => null,
            'is_test' => false,
        ];
    }

    public function failed(string $error = 'Invalid OAuth access token'): static
    {
        return $this->state(fn (): array => ['status' => 'failed', 'events_received' => null, 'fbtrace_id' => null, 'error' => $error]);
    }
}
