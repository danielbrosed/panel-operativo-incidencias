<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * For tests that need rows, not history: it writes incidents directly, without trail entries.
 * Tests about the trail create incidents through App\Actions\Incidents\CreateIncident instead.
 *
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $priority = fake()->randomElement(IncidentPriority::cases());

        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => IncidentStatus::Open,
            'priority' => $priority,
            'requester_name' => fake()->name(),
            'assigned_to' => null,
            'store' => fake()->company(),
            'due_at' => now()->addHours($priority->slaHours()),
        ];
    }

    public function status(IncidentStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function priority(IncidentPriority $priority): static
    {
        return $this->state(['priority' => $priority, 'due_at' => now()->addHours($priority->slaHours())]);
    }
}
